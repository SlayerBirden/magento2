<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Categories tree block
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Catalog\Block\Adminhtml\Category;

use Magento\Catalog\Model\Resource\Category\Collection;
use Magento\Framework\Data\Tree\Node;

class Tree extends \Magento\Catalog\Block\Adminhtml\Category\AbstractCategory
{
    /**
     * @var string
     */
    protected $_template = 'catalog/category/tree.phtml';

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_backendSession;

    /**
     * @var \Magento\Framework\DB\Helper
     */
    protected $_resourceHelper;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Catalog\Model\Resource\Category\Tree $categoryTree
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\DB\Helper $resourceHelper
     * @param \Magento\Backend\Model\Auth\Session $backendSession
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Catalog\Model\Resource\Category\Tree $categoryTree,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\DB\Helper $resourceHelper,
        \Magento\Backend\Model\Auth\Session $backendSession,
        array $data = array()
    ) {
        $this->_jsonEncoder = $jsonEncoder;
        $this->_resourceHelper = $resourceHelper;
        $this->_backendSession = $backendSession;
        parent::__construct($context, $categoryTree, $registry, $categoryFactory, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setUseAjax(0);
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        $addUrl = $this->getUrl("*/*/add", array('_current' => true, 'id' => null, '_query' => false));

        $this->addChild(
            'add_sub_button',
            'Magento\Backend\Block\Widget\Button',
            array(
                'label' => __('Add Subcategory'),
                'onclick' => "addNew('" . $addUrl . "', false)",
                'class' => 'add',
                'id' => 'add_subcategory_button',
                'style' => $this->canAddSubCategory() ? '' : 'display: none;'
            )
        );

        if ($this->canAddRootCategory()) {
            $this->addChild(
                'add_root_button',
                'Magento\Backend\Block\Widget\Button',
                array(
                    'label' => __('Add Root Category'),
                    'onclick' => "addNew('" . $addUrl . "', true)",
                    'class' => 'add',
                    'id' => 'add_root_category_button'
                )
            );
        }

        return parent::_prepareLayout();
    }

    /**
     * Retrieve list of categories with name containing $namePart and their parents
     *
     * @param string $namePart
     * @return string
     */
    public function getSuggestedCategoriesJson($namePart)
    {
        $storeId = $this->getRequest()->getParam('store', $this->_getDefaultStoreId());

        /* @var $collection Collection */
        $collection = $this->_categoryFactory->create()->getCollection();

        $matchingNamesCollection = clone $collection;
        $escapedNamePart = $this->_resourceHelper->addLikeEscape(
            $namePart,
            array('position' => 'any')
        );
        $matchingNamesCollection->addAttributeToFilter(
            'name',
            array('like' => $escapedNamePart)
        )->addAttributeToFilter(
            'entity_id',
            array('neq' => \Magento\Catalog\Model\Category::TREE_ROOT_ID)
        )->addAttributeToSelect(
            'path'
        )->setStoreId(
            $storeId
        );

        $shownCategoriesIds = array();
        foreach ($matchingNamesCollection as $category) {
            foreach (explode('/', $category->getPath()) as $parentId) {
                $shownCategoriesIds[$parentId] = 1;
            }
        }

        $collection->addAttributeToFilter(
            'entity_id',
            array('in' => array_keys($shownCategoriesIds))
        )->addAttributeToSelect(
            array('name', 'is_active', 'parent_id')
        )->setStoreId(
            $storeId
        );

        $categoryById = array(
            \Magento\Catalog\Model\Category::TREE_ROOT_ID => array(
                'id' => \Magento\Catalog\Model\Category::TREE_ROOT_ID,
                'children' => array()
            )
        );
        foreach ($collection as $category) {
            foreach (array($category->getId(), $category->getParentId()) as $categoryId) {
                if (!isset($categoryById[$categoryId])) {
                    $categoryById[$categoryId] = array('id' => $categoryId, 'children' => array());
                }
            }
            $categoryById[$category->getId()]['is_active'] = $category->getIsActive();
            $categoryById[$category->getId()]['label'] = $category->getName();
            $categoryById[$category->getParentId()]['children'][] =& $categoryById[$category->getId()];
        }

        return $this->_jsonEncoder->encode($categoryById[\Magento\Catalog\Model\Category::TREE_ROOT_ID]['children']);
    }

    /**
     * @return string
     */
    public function getAddRootButtonHtml()
    {
        return $this->getChildHtml('add_root_button');
    }

    /**
     * @return string
     */
    public function getAddSubButtonHtml()
    {
        return $this->getChildHtml('add_sub_button');
    }

    /**
     * @return string
     */
    public function getExpandButtonHtml()
    {
        return $this->getChildHtml('expand_button');
    }

    /**
     * @return string
     */
    public function getCollapseButtonHtml()
    {
        return $this->getChildHtml('collapse_button');
    }

    /**
     * @return string
     */
    public function getStoreSwitcherHtml()
    {
        return $this->getChildHtml('store_switcher');
    }

    /**
     * @param bool|null $expanded
     * @return string
     */
    public function getLoadTreeUrl($expanded = null)
    {
        $params = array('_current' => true, 'id' => null, 'store' => null);
        if (is_null($expanded) && $this->_backendSession->getIsTreeWasExpanded() || $expanded == true) {
            $params['expand_all'] = true;
        }
        return $this->getUrl('*/*/categoriesJson', $params);
    }

    /**
     * @return string
     */
    public function getNodesUrl()
    {
        return $this->getUrl('catalog/category/jsonTree');
    }

    /**
     * @return string
     */
    public function getSwitchTreeUrl()
    {
        return $this->getUrl(
            'catalog/category/tree',
            array('_current' => true, 'store' => null, '_query' => false, 'id' => null, 'parent' => null)
        );
    }

    /**
     * @return bool
     */
    public function getIsWasExpanded()
    {
        return $this->_backendSession->getIsTreeWasExpanded();
    }

    /**
     * @return string
     */
    public function getMoveUrl()
    {
        return $this->getUrl('catalog/category/move', array('store' => $this->getRequest()->getParam('store')));
    }

    /**
     * @param mixed|null $parenNodeCategory
     * @return array
     */
    public function getTree($parenNodeCategory = null)
    {
        $rootArray = $this->_getNodeJson($this->getRoot($parenNodeCategory));
        $tree = isset($rootArray['children']) ? $rootArray['children'] : array();
        return $tree;
    }

    /**
     * @param mixed|null $parenNodeCategory
     * @return string
     */
    public function getTreeJson($parenNodeCategory = null)
    {
        $rootArray = $this->_getNodeJson($this->getRoot($parenNodeCategory));
        $json = $this->_jsonEncoder->encode(isset($rootArray['children']) ? $rootArray['children'] : array());
        return $json;
    }

    /**
     * Get JSON of array of categories, that are breadcrumbs for specified category path
     *
     * @param string $path
     * @param string $javascriptVarName
     * @return string
     */
    public function getBreadcrumbsJavascript($path, $javascriptVarName)
    {
        if (empty($path)) {
            return '';
        }

        $categories = $this->_categoryTree->setStoreId($this->getStore()->getId())->loadBreadcrumbsArray($path);
        if (empty($categories)) {
            return '';
        }
        foreach ($categories as $key => $category) {
            $categories[$key] = $this->_getNodeJson($category);
        }
        return '<script type="text/javascript">' . $javascriptVarName . ' = ' . $this->_jsonEncoder->encode(
            $categories
        ) .
            ';' .
            ($this->canAddSubCategory() ? '$("add_subcategory_button").show();' : '$("add_subcategory_button").hide();') .
            '</script>';
    }

    /**
     * Get JSON of a tree node or an associative array
     *
     * @param Node|array $node
     * @param int $level
     * @return string
     */
    protected function _getNodeJson($node, $level = 0)
    {
        // create a node from data array
        if (is_array($node)) {
            $node = new Node($node, 'entity_id', new \Magento\Framework\Data\Tree());
        }

        $item = array();
        $item['text'] = $this->buildNodeName($node);

        $rootForStores = in_array($node->getEntityId(), $this->getRootIds());

        $item['id'] = $node->getId();
        $item['store'] = (int)$this->getStore()->getId();
        $item['path'] = $node->getData('path');

        $item['cls'] = 'folder ' . ($node->getIsActive() ? 'active-category' : 'no-active-category');
        //$item['allowDrop'] = ($level<3) ? true : false;
        $allowMove = $this->_isCategoryMoveable($node);
        $item['allowDrop'] = $allowMove;
        // disallow drag if it's first level and category is root of a store
        $item['allowDrag'] = $allowMove && ($node->getLevel() == 1 && $rootForStores ? false : true);

        if ((int)$node->getChildrenCount() > 0) {
            $item['children'] = array();
        }

        $isParent = $this->_isParentSelectedCategory($node);

        if ($node->hasChildren()) {
            $item['children'] = array();
            if (!($this->getUseAjax() && $node->getLevel() > 1 && !$isParent)) {
                foreach ($node->getChildren() as $child) {
                    $item['children'][] = $this->_getNodeJson($child, $level + 1);
                }
            }
        }

        if ($isParent || $node->getLevel() < 2) {
            $item['expanded'] = true;
        }

        return $item;
    }

    /**
     * Get category name
     *
     * @param \Magento\Framework\Object $node
     * @return string
     */
    public function buildNodeName($node)
    {
        $result = $this->escapeHtml($node->getName());
        if ($this->_withProductCount) {
            $result .= ' (' . $node->getProductCount() . ')';
        }
        return $result;
    }

    /**
     * @param Node|array $node
     * @return bool
     */
    protected function _isCategoryMoveable($node)
    {
        $options = new \Magento\Framework\Object(array('is_moveable' => true, 'category' => $node));

        $this->_eventManager->dispatch('adminhtml_catalog_category_tree_is_moveable', array('options' => $options));

        return $options->getIsMoveable();
    }

    /**
     * @param Node|array $node
     * @return bool
     */
    protected function _isParentSelectedCategory($node)
    {
        if ($node && $this->getCategory()) {
            $pathIds = $this->getCategory()->getPathIds();
            if (in_array($node->getId(), $pathIds)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if page loaded by outside link to category edit
     *
     * @return boolean
     */
    public function isClearEdit()
    {
        return (bool)$this->getRequest()->getParam('clear');
    }

    /**
     * Check availability of adding root category
     *
     * @return boolean
     */
    public function canAddRootCategory()
    {
        $options = new \Magento\Framework\Object(array('is_allow' => true));
        $this->_eventManager->dispatch(
            'adminhtml_catalog_category_tree_can_add_root_category',
            array('category' => $this->getCategory(), 'options' => $options, 'store' => $this->getStore()->getId())
        );

        return $options->getIsAllow();
    }

    /**
     * Check availability of adding sub category
     *
     * @return boolean
     */
    public function canAddSubCategory()
    {
        $options = new \Magento\Framework\Object(array('is_allow' => true));
        $this->_eventManager->dispatch(
            'adminhtml_catalog_category_tree_can_add_sub_category',
            array('category' => $this->getCategory(), 'options' => $options, 'store' => $this->getStore()->getId())
        );

        return $options->getIsAllow();
    }
}

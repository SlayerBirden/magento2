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
namespace Magento\Catalog\Service\V1\Product\Attribute\Media;

use \Magento\Catalog\Model\Product;

class GalleryEntryResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var GalleryEntryResolver
     */
    private $entryResolver;

    protected function setUp()
    {
        $this->entryResolver = new GalleryEntryResolver();
    }

    public function testGetEntryFilePathById()
    {
        $productMock = $this->getMock('Magento\Catalog\Model\Product', array(), array(), '', false);
        $productMock->expects($this->any())->method('getData')->with('media_gallery')->will($this->returnValue(array(
            'images' => array(
                array(
                    'file' => '/i/m/image.jpg',
                    'value_id' => 1,
                ),
                array(
                    'file' => '/i/m/image2.jpg',
                    'value_id' => 2,
                ),
            ),
        )));
        $this->assertEquals('/i/m/image2.jpg', $this->entryResolver->getEntryFilePathById($productMock, 2));
        $this->assertNull($this->entryResolver->getEntryFilePathById($productMock, 9999));
    }

    public function testGetEntryIdByFilePath()
    {
        $productMock = $this->getMock('Magento\Catalog\Model\Product', array(), array(), '', false);
        $productMock->expects($this->any())->method('getData')->with('media_gallery')->will($this->returnValue(array(
            'images' => array(
                array(
                    'file' => '/i/m/image2.jpg',
                    'value_id' => 2,
                ),
                array(
                    'file' => '/i/m/image.jpg',
                    'value_id' => 1,
                ),
            ),
        )));
        $this->assertEquals(1, $this->entryResolver->getEntryIdByFilePath($productMock, '/i/m/image.jpg'));
        $this->assertNull($this->entryResolver->getEntryIdByFilePath($productMock, '/i/m/non_existent_image.jpg'));
    }
}

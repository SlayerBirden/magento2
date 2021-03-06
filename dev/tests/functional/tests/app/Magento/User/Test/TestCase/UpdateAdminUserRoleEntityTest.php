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

namespace Magento\User\Test\TestCase;

use Magento\Backend\Test\Page\AdminAuthLogin;
use Magento\Backend\Test\Page\Dashboard;
use Magento\User\Test\Page\Adminhtml\UserRoleIndex;
use Magento\User\Test\Page\Adminhtml\UserRoleEditRole;
use Magento\User\Test\Fixture\User;
use Magento\User\Test\Fixture\AdminUserRole;
use Mtf\TestCase\Injectable;

/**
 * Test Creation for UpdateAdminUserRoleEntity
 *
 * Test Flow:
 * Preconditions:
 * 1. Create new admin user and assign it to new role.
 * Steps:
 * 1. Log in as admin user from data set.
 * 2. Go to System>Permissions>User Roles
 * 3. Open role created in precondition
 * 4. Fill in data according to data set
 * 5. Perform all assertions
 *
 * @group ACL_(PS)
 * @ZephyrId MAGETWO-24768
 */
class UpdateAdminUserRoleEntityTest extends Injectable
{
    /**
     * @var UserRoleIndex
     */
    protected $rolePage;

    /**
     * @var UserRoleEditRole
     */
    protected $userRoleEditRole;

    /**
     * @var AdminAuthLogin
     */
    protected $adminAuthLogin;

    /**
     * @var Dashboard
     */
    protected $dashboard;

    /**
     * Setup data for test
     *
     * @param UserRoleIndex $rolePage
     * @param UserRoleEditRole $userRoleEditRole
     * @param AdminAuthLogin $adminAuthLogin
     * @param Dashboard $dashboard
     * @return void
     */
    public function __inject(
        UserRoleIndex $rolePage,
        UserRoleEditRole $userRoleEditRole,
        AdminAuthLogin $adminAuthLogin,
        Dashboard $dashboard
    ) {
        $this->rolePage = $rolePage;
        $this->userRoleEditRole = $userRoleEditRole;
        $this->adminAuthLogin = $adminAuthLogin;
        $this->dashboard = $dashboard;
    }

    /**
     * Runs Update Admin User Roles Entity test
     *
     * @param AdminUserRole $roleInit
     * @param AdminUserRole $role
     * @param User $user
     * @return array
     */
    public function testUpdateAdminUserRolesEntity(
        AdminUserRole $roleInit,
        AdminUserRole $role,
        User $user
    ) {
        // Preconditions
        $roleInit->persist();
        if (!$user->hasData('user_id')) {
            $user->persist();
        }

        // Steps
        $filter = ['rolename' => $roleInit->getRoleName()];
        $this->adminAuthLogin->open();
        $this->adminAuthLogin->getLoginBlock()->fill($user);
        $this->adminAuthLogin->getLoginBlock()->submit();
        $this->rolePage->open();
        $this->rolePage->getRoleGrid()->searchAndOpen($filter);
        $this->userRoleEditRole->getRoleFormTabs()->fill($role);
        $this->userRoleEditRole->getPageActions()->save();

        return [
            'customAdmin' => $role->hasData('in_role_users')
                ? $role->getDataFieldConfig('in_role_users')['source']->getAdminUsers()[0]
                : $user,
        ];
    }

    /**
     * Logout Admin User from account
     *
     * @return void
     */
    public function tearDown()
    {
        $this->dashboard->getAdminPanelHeader()->logOut();
    }
}

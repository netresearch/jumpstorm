<?php

use Netresearch\Config;
use Netresearch\PluginInterface as JumpstormPlugin;

require_once 'AbstractCreator.php';
require_once 'UserCreator.php';
require_once 'RoleCreator.php';
require_once 'PermissionsCreator.php';

/**
 * Create demo admin user, roles and permissions
 */
class CreateBackendUser implements JumpstormPlugin
{
    /**
     * @var Config
     */
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function execute()
    {
        $userCreator = new UserCreator($this->config, 'user');
        $user = $userCreator->createUser(Mage::getModel('admin/user'));
        
        $roleCreator = new RoleCreator($this->config, 'role');
        $groupRole = $roleCreator->createGroupRole(Mage::getModel('admin/role'));
        $userRole = $roleCreator->createUserRole(Mage::getModel('admin/role'), $groupRole, $user);
        
        $permissionsCreator = new PermissionsCreator($this->config, 'permissions');
        $rules = $permissionsCreator->createPermissions(Mage::getModel('admin/rules'), $groupRole);
    }
}

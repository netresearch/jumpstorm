<?php

use Netresearch\Config;
use Netresearch\PluginInterface as JumpstormPlugin;

/**
 * disable admin notifications
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
        $userCreator = new UserCreator($config->user);
        $user = $userCreator->createUser(Mage::getModel('admin/user'));
        
        $roleCreator = new RoleCreator($config->role);
        $groupRole = $roleCreator->createGroupRole(Mage::getModel('admin/role'));
        $userRole = $roleCreator->createUserRole(Mage::getModel('admin/role'), $groupRole, $user);
        
        $permissionsCreator = new PermissionsCreator($config->permissions);
        $rules = $permissionsCreator->createPermissions(Mage::getModel('admin/rules'), $groupRole);
    }
}

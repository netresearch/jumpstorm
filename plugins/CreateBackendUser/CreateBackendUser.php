<?php

namespace CreateBackendUser;

use CreateBackendUser\UserCreator;
use \Mage as Mage;
use Netresearch\Config\Base as BaseConfig;
use Netresearch\PluginInterface as JumpstormPlugin;

/**
 * Create demo admin user, roles and permissions
 */
class CreateBackendUser implements JumpstormPlugin
{
    /**
     * @var Config
     */
    protected $config;

    public function __construct(BaseConfig $config)
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

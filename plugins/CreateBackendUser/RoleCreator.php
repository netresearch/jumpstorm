<?php

use Netresearch\Config;
use \Exception as Exception;

/**
 * RoleCreator
 *
 * @category    Plugins
 * @package     Plugins_CreateBackendUser
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 * 
 * @property string $name
 */
class RoleCreator extends AbstractCreator
{
    const TYPE_GROUP = 'G';
    const TYPE_USER = 'U';
    const TREE_LEVEL_GROUP = 1;
    const TREE_LEVEL_USER = 2;
    
    
    /**
     * (non-PHPdoc)
     * @see AbstractCreator::validateProperty()
     */
    protected function validateProperty($key, $value = null)
    {
        if (null === $value) {
            $value = $this->{$key};
        }

        if ($key === 'name' && empty($value)) {
            throw new Exception('Please set \'name\' in ini file.');
        }
        
        return true;
    }
        
    public function createGroupRole(Mage_Admin_Model_Role $role)
    {
        return $role
            ->setRoleName($this->name)
//             ->setUserId(0)
            ->setRoleType(self::TYPE_GROUP)
            ->setTreeLevel(self::TREE_LEVEL_GROUP)
//             ->setParentId(0)
            ->save();
    }
    
    public function createUserRole(Mage_Admin_Model_Role $role,
            Mage_Admin_Model_Role $parentRole, Mage_Admin_Model_User $user)
    {
        return $role
            ->setRoleName($this->name)
            ->setUserId($user->getId())
            ->setRoleType(self::TYPE_USER)
            ->setTreeLevel(self::TREE_LEVEL_USER)
            ->setParentId($parentRole->getId())
            ->save();
    }
}
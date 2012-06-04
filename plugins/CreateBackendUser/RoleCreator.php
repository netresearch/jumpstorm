<?php

namespace CreateBackendUser;

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
        
    /**
     * (non-PHPdoc)
     * @see AbstractCreator::getAllowedProperties()
     */
    protected function getAllowedProperties()
    {
        return array(
            'name',
        );
    }
    
    /**
     * Create a new group.
     * 
     * @param Mage_Admin_Model_Role $role
     */
    public function createGroupRole(Mage_Admin_Model_Role $role)
    {
        $roles = Mage::getModel('admin/role')->getCollection();
        /* just return existing role if name matches */
        foreach ($roles as $role) {
            if ($role->getRoleName() == $this->name) {
                return $role;
            }
        }
        
        return $role
            ->setRoleName($this->name)
            ->setRoleType(self::TYPE_GROUP)
            ->setTreeLevel(self::TREE_LEVEL_GROUP)
            ->save();
    }
    
    /**
     * Add a user to a group.
     * 
     * @param Mage_Admin_Model_Role $role
     * @param Mage_Admin_Model_Role $parentRole
     * @param Mage_Admin_Model_User $user
     */
    public function createUserRole(Mage_Admin_Model_Role $role,
            Mage_Admin_Model_Role $parentRole, Mage_Admin_Model_User $user)
    {
        return $role
            ->setRoleName($parentRole->getRoleName())
            ->setUserId($user->getId())
            ->setRoleType(self::TYPE_USER)
            ->setTreeLevel(self::TREE_LEVEL_USER)
            ->setParentId($parentRole->getId())
            ->save();
    }
}

<?php

namespace CreateBackendUser;

use \Exception as Exception;

/**
 * UserCreator
 *
 * @category    Plugins
 * @package     Plugins_CreateBackendUser
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 * 
 * @property string $username
 * @property string $firstname
 * @property string $lastname
 * @property string $email
 * @property string $password
 * @property boolean $is_active
 */
class UserCreator extends AbstractCreator
{
    /**
     * (non-PHPdoc)
     * @see AbstractCreator::validateProperty()
     */
    protected function validateProperty($key, $value = null)
    {
        if (null === $value) {
            $value = $this->{$key};
        }
        
        if ($key === 'username' && empty($value)) {
            throw new Exception('Please set \'username\' in ini file.');
        } elseif ($key === 'firstname' && empty($value)) {
            throw new Exception('Please set \'firstname\' in ini file.');
        } elseif ($key === 'lastname' && empty($value)) {
            throw new Exception('Please set \'lastname\' in ini file.');
        } elseif ($key === 'email' && empty($value)) {
            throw new Exception('Please set \'email \'in ini file.');
        } elseif ($key === 'password' && empty($value)) {
            throw new Exception('Please set \'password\' in ini file.');
        } elseif ($key === 'is_active' && !is_bool($value)) {
            $this->{$key} = (bool)$value;
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
            'username',
            'firstname',
            'lastname',
            'email',
            'password',
            'is_active'
        );
    }

    /**
     * Set the data from ini file to the user object and save.
     * 
     * @param Mage_Admin_Model_User $user A user object
     * @return Mage_Admin_Model_User
     */
    public function createUser(Mage_Admin_Model_User $user)
    {
        return $user->setData($this->data)->save();
    }
}

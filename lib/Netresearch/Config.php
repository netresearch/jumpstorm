<?php
class Config extends Zend_Config_Ini
{
    protected $_dbName;

    protected $addedPermissions;
    protected $removedPermissions;

    protected $sampleDataVersions = array(
        '1', '1.1.2', '1.2.0', '1.6.1.0'
    );
    
    public function getInstallPath()
    {
        $target = $this->common->target;
        if (!$target) {
            throw new \Exception('target path is not set');
        }
        return $target;
    }

    public function getMagentoPath()
    {
        $source = $this->magento->source;
        if (!$source) {
            throw new \Exception('Magento source path is not set');
        }
        return $source;
    }

    public function getMagentoBaseUrl()
    {
        return $this->magento->magentoBaseUrl;
    }

    public function getMagentoCheckout()
    {
        return $this->magento->magentoCheckout;
    }

    public function getTesting()
    {
        return $this->magento->magentoTesting;
    }

    /**
     * @deprecated
     * @see Config::getMagentoSampleDataVersion()
     */
    public function hasMagentoSampleData()
    {
        return (1 == $this->magento->magentoSampledata);
    }

    /**
     * 
     * @return string|false Sample data version if valid, false otherwise
     */
    public function getMagentoSampleDataVersion()
    {
        if (empty($this->magento->magentoSampledata)) {
            return false;
        }
        // default version for backwards compatibility with older ini files
        if ($this->magento->magentoSampledata === '1') {
            return '1.2.0';
        }
        
        // version as given in ini-file
        if (in_array($this->magento->magentoSampledata, $this->sampleDataVersions)) {
            return $this->magento->magentoSampledata;
        }

        // inform user about valid sample data if nothing is known about the specified version
        throw new Exception(sprintf(
            'Invalid sample data version given in jumpstorm.ini: %s – Use one out of %s',
            $this->magento->magentoSampledata,
            implode(', ', $this->sampleDataVersions)
        ));
    }
    
    public function getEnvironment()
    {
        return $this->common->environment;
    }

    public function getExtensions()
    {
        return $this->extensions;
    }

    public function getDbName()
    {
        if (is_null($this->_dbName)) {
            $this->_dbName = $this->db->dbName;
           
            if ($this->db->dbTimestamp) {
                $this->_dbName .= '_' . time();
            }
        }

        return $this->_dbName;
    }

    public function getDbUser()
    {
        return $this->db->dbUser;
    }

    public function getDbHost()
    {
        return $this->db->dbHost;
    }

    public function getDbPass()
    {
        return $this->db->dbPass;
    }

    public function getDbPrefix()
    {
        return $this->db->dbPrefix;
    }

    public function getAdminFirstname()
    {
        return $this->magento->adminFirstname;
    }

    public function getAdminLastname()
    {
        return $this->magento->adminLastname;
    }

    public function getAdminEmail()
    {
        return $this->magento->adminEmail;
    }

    public function getAdminUser()
    {
        return $this->magento->adminUser;
    }

    public function getAdminPass()
    {
        return $this->magento->adminPass;
    }

    protected function assignPermissions()
    {
        $this->addedPermissions   = array();
        $this->removedPermissions = array();

        if (isset($this->permissions)) {
            foreach ($this->permissions as $permission=>$allowed) {
                if (1 == $allowed) {
                    $this->addedPermissions[] = $permission;
                } elseif (0 == $allowed) {
                    $this->removedPermissions[] = $permission;
                } else {
                    throw new Exception(sprintf('invalid value %s for permission %s in jumpstorm.ini!', $allowed, $permission));
                }
            }
        }
    }

    public function getRemovedPermissions()
    {
        if (is_null($this->removedPermissions)) {
            $this->assignPermissions();
        }
        return $this->removedPermissions;
    }

    public function getAddedPermissions()
    {
        if (is_null($this->addedPermissions)) {
            $this->assignPermissions();
        }
        return $this->addedPermissions;
    }
}

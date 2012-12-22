<?php
namespace Netresearch;

use Symfony\Component\Yaml\Yaml;
use Netresearch\Config\Base;

class Config extends Base
{
    protected $_dbName;

    protected $addedPermissions;
    protected $removedPermissions;

    /**
     * get base target path
     *
     * @return string
     */
    public function getTarget()
    {
        return $this->common->magento->target;
    }

    public function disableInteractivity()
    {
        $this->ask = false;
        $this->confirm = false;
    }

    /**
     * get Magento source path
     *
     * @return string
     */
    public function getMagentoSource()
    {
        $source = $this->magento->source;
        if (!$source) {
            throw new \Exception('Magento source path is not set');
        }
        return $source;
    }

    public function getMagentoBranch()
    {
        return $this->magento->branch ? $this->magento->branch : null;
    }

    public function getMagentoBaseUrl()
    {
        return $this->magento->baseUrl;
    }

    /**
     * get source identifier for Magento sample data
     *
     * @return string|null
     */
    public function getMagentoSampledataSource()
    {
        if ($this->magento && $this->magento->sampledata) {
            $value = $this->magento->sampledata;
            if (is_array($value)) {
                return ($value->source) ? $value->source : null;
            }
            return $value;
        }
    }

    /**
     * get branch identifier for Magento sample data
     *
     * @return string|null
     */
    public function getMagentoSampledataBranch()
    {
        if ($this->magento && $this->magento->sampledata) {
            $value = $this->magento->sampledata;
            if (is_array($value) && $value->branch) {
                return $value->branch;
            }
        }
    }

    /**
     * get extensions as array (name => [branch, source])
     *
     * @return array
     */
    public function getExtensions()
    {
        $extensions = array();
        foreach ($this->extensions->data as $name=>$extension) {
            if (is_array($extension)) {
                $extensions[$name] = new \StdClass();
                $extensions[$name]->source = $extension['source'];
                $extensions[$name]->branch = 'master';
                if (array_key_exists('branch', $extension)) {
                    $extensions[$name]->branch = $extension['branch'];
                }
            } else {
                $extensions[$name] = new \StdClass();
                $extensions[$name]->source = $extension;
                $extensions[$name]->branch = 'master';
            }
        }
        return $extensions;
    }

    public function getDbName()
    {
        if (is_null($this->_dbName)) {
            $this->_dbName = $this->common->db->name;

            if ($this->common->db->timestamp) {
                $this->_dbName .= '_' . time();
            }
        }

        return $this->_dbName;
    }

    public function getDbUser()
    {
        return $this->common->db->user;
    }

    public function getDbHost()
    {
        return $this->common->db->host;
    }

    public function getDbPass()
    {
        return ($this->common->db->pass) ? $this->common->db->pass : null;
    }

    public function getDbPrefix()
    {
        return $this->common->db->prefix;
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

    /**
     * collect admin user permissions from configuration
     *
     * @return void
     */
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

    public function getPlugins()
    {
        return $this->plugins->data;
    }
}

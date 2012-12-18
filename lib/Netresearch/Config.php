<?php
namespace Netresearch;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

class Config extends \Zend_Config_Ini
{
    protected $_dbName;

    protected $confirmedData = array();

    protected $output;
    protected $command;

    protected $addedPermissions;
    protected $removedPermissions;
    
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }
    
    public function setCommand(Command $command)
    {
        $this->command = $command;
    }

    /**
     * get target path
     * 
     * @return string
     */
    public function getTarget()
    {
        $path = 'common.magento.target';
        return $this->determine($path);
    }

    public function disableInteractivity()
    {
        $this->ask = false;
        $this->confirm = false;
    }

    public function determine($path)
    {
        if (array_key_exists($path, $this->confirmedData)) {
            return $this->confirmedData[$path];
        }
        $readablePath = ucwords(str_replace('.', ' ', $path));
        $steps = explode('.', $path);
        $value = $this;
        $step = current($steps);
        while ($value instanceof \Zend_Config) {
            $value = $value->$step;
            $step = next($steps);
        }
        if (is_null($value) && $this->ask && in_array($path, $this->ask)) {
            $dialog = $this->command->getHelperSet()->get('dialog');
            $value = $dialog->ask(
                $this->output,
                sprintf('<question>%s?</question> ', $readablePath),
                false
            );
            $subConfig = $this;
            foreach ($steps as $step) {
                $subConfig = $subConfig->$step;
            }
            $subConfig = $value;
        }
        if ($this->confirm && in_array($path, $this->confirm->toArray())) {
            $dialog = $this->command->getHelperSet()->get('dialog');
            $confirmation = $dialog->askConfirmation(
                $this->output,
                sprintf('<question>%s %s (y)?</question> ', $readablePath, $value),
                true
            );
            if (!$confirmation) {
                throw new \Exception(sprintf(
                    'Stopped execution due to unconfirmed %s!',
                    $readablePath
                ));
            }
        }
        $this->confirmedData[$path] = $value;
        return $value;
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
            if ($value instanceof \Zend_Config) {
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
            if ($value instanceof \Zend_Config && $value->branch) {
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
        foreach ($this->extensions as $name=>$extension) {
            if (!is_string($extension)) {
                $extensions[$name] = $extension;
            } else {
                $extensions[$name] = new \StdClass();
                $extensions[$name]->branch = 'master';
                $extensions[$name]->source = $extension;
            }
        }
        return $extensions;
    }

    public function getDbName()
    {
        if (is_null($this->_dbName)) {
            $path = 'common.db.name';
            $this->_dbName = $this->determine($path);
           
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
        return $this->plugins;
    }
}

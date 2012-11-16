<?php
namespace InitGermanSetup;

use \Mage as Mage;
use Netresearch\Config;
use Netresearch\Logger;
use Netresearch\PluginInterface as JumpstormPlugin;

/**
 * initialize German Setup
 */
class InitGermanSetup implements JumpstormPlugin
{
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function execute()
    { 
        Mage::app('admin');
        \Mage_Core_Model_Resource_Setup::applyAllUpdates();
        \Mage_Core_Model_Resource_Setup::applyAllDataUpdates();

        try {
            $this->setup();
        } catch (Exception $e) {
            $msg.= $e->getMessage() . ' (' . $e->getFile() . ' l. ' . $e->getLine() . ")\n";
            Logger::error('An error occured while initializing InitGermanSetup:', array(), false);
            Logger::log('%s (%s, line %s)', array($e->getMessage(), $e->getFile(), $e->getLine()));
        }

    }

    protected function setup()
    {
        Mage::getSingleton('germansetup/setup_cms')->setup();
        Mage::getSingleton('germansetup/setup_agreements')->setup();
        $locale = Mage::app()->getRequest()->setPost('email_locale', 'de_DE');
        Mage::getSingleton('germansetup/setup_email')->setup();
        Mage::getSingleton('germansetup/setup_tax')->setup();

        // default
        Mage::getSingleton('germansetup/setup_tax')->updateProductTaxClasses(1, 1);
        // taxable goods
        Mage::getSingleton('germansetup/setup_tax')->updateProductTaxClasses(2, 1);
        // shipping
        Mage::getSingleton('germansetup/setup_tax')->updateProductTaxClasses(4, 4);

        Mage::getModel('eav/entity_setup', 'core_setup')->setConfigData('germansetup/is_initialized', '1');
    }
}



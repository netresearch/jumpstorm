<?php
namespace ApplyConfigSettings;

use \Mage as Mage;
use Netresearch\Config\Base as BaseConfig;
use Netresearch\Logger;
use Netresearch\PluginInterface as JumpstormPlugin;

/**
 * apply some settings
 */
class ApplyConfigSettings implements JumpstormPlugin
{
    protected $config;

    public function __construct(BaseConfig $config)
    {
        $this->config = $config;
    }

    public function execute()
    {
        $settings = $this->config->plugins->ApplyConfigSettings;
        if ($settings instanceof BaseConfig) {
            foreach ($settings as $name=>$setting) {
                if (2 == substr_count($name, '/')
                    && is_scalar($setting)
                ) {
                    Mage::getModel('eav/entity_setup', 'core_setup')->setConfigData(
                        $name,
                        $setting
                    );
                    Logger::log('* Applied setting %s', array($name));
                } elseif ($setting instanceof BaseConfig
                    && $setting->path
                    && $setting->value
                ) {
                    Mage::getModel('eav/entity_setup', 'core_setup')->setConfigData(
                        $setting->path,
                        $setting->value
                    );
                    Logger::log('* Applied setting %s', array($name));
                } else {
                    Logger::error(
                        'Could not apply setting %s due to invalid configuration',
                        array($name),
                        false
                    );
                }
            }
        } else {
            Logger::error('Invalid configuration for plugin ApplyConfigSettings', array(), false);
        }
    }
}



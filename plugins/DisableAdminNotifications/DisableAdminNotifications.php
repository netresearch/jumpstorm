<?php
use Netresearch\Config;
use Netresearch\Logger;

/**
 * disable admin notifications
 */
class DisableAdminNotifications
{
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function execute()
    {
        Mage::getModel('eav/entity_setup', 'core_setup')->setConfigData(
            'advanced/modules_disable_output/Mage_AdminNotification',
            '1'
        );
    }
}



<?php
namespace FlushCache;

use \Mage as Mage;
use Netresearch\Config;
use Netresearch\PluginInterface as JumpstormPlugin;

/**
 * flush Magento cache
 */
class FlushCache implements JumpstormPlugin
{
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function execute()
    {
        Mage::getModel('core/cache')->flush();
    }
}



<?php
namespace RobotsTxt;

use Netresearch\Config;
use Netresearch\Logger;
use Netresearch\PluginInterface as JumpstormPlugin;

/**
 * Create robots.txt from config. No sanity checks are applied.
 *
 * @package    Jumpstorm
 * @subpackage Plugins
 * @author     Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class RobotsTxt implements JumpstormPlugin
{
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function execute()
    {
        $filename = $this->config->getTarget() . '/robots.txt';
        $lines = $this->config->plugins->RobotsTxt->lines->toArray();
        if (false === file_put_contents($filename, implode(PHP_EOL, $lines))) {
            Logger::error('An error occured while creating robots.txt', array(), false);
        }
    }
}

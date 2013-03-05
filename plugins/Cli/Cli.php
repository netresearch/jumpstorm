<?php
namespace Cli;

use Netresearch\Config;
use Netresearch\Logger;
use Netresearch\PluginInterface as JumpstormPlugin;

/**
 * Perform some command line action. No sanity checks are applied.
 *
 * @package    Jumpstorm
 * @subpackage Plugins
 * @author     Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class Cli implements JumpstormPlugin
{
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function execute()
    {
        $commands = $this->config->plugins->Cli->commands->toArray();
        foreach ($commands as $command) {
            $cmd = escapeshellcmd($command);
            Logger::comment("Running custom command: $cmd");
            exec($cmd);
        }
    }
}

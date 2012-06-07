<?php
namespace Jumpstorm;

use Netresearch\Config;
use Netresearch\Logger;
use Netresearch\Source\Base as Source;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use \Exception as Exception;

/**
 * install extensions
 *
 * @package    Jumpstorm
 * @subpackage Jumpstorm
 * @author     Thomas Birke <thomas.birke@netresearch.de>
 */
class Plugins extends Base
{
    /**
     * @see vendor/symfony/src/Symfony/Component/Console/Command/Symfony\Component\Console\Command.Command::configure()
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('plugins');
        $this->setDescription('Run Jumpstorm plugins');
    }

    /**
     * @see vendor/symfony/src/Symfony/Component/Console/Command/Symfony\Component\Console\Command.Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->preExecute($input, $output);

        $this->initMagento();

        $plugins = $this->config->getPlugins();
        foreach ($plugins as $name => $settings) {
            // check if plugin was defined in ini, but disabled
            if ('0' === $settings->enabled) {
                Logger::log('Skipping plugin "%s"', array($name));
                continue;
            }

            // set path to plugin by convention
            $path = $this->getBasePath() . 'plugins' . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR;
            
            // load script file
            $file =  $path . $name . '.php';
            if (!file_exists($file)) {
                Logger::error('Could not find plugin "%s"', array($name), $stop=false);
                Logger::log('Expected it at path "%s"', array($path));
                continue;
            }

            // load default jumpstorm config for plugin execution
            $pluginConfig = $this->config;

            $customIni = $settings->ini;
            if ((null !== $customIni) && file_exists($customIni)) {
                // add custom config settings, if given
                $pluginConfig = new Config($customIni, null, array('allowModifications' => true));
                $pluginConfig->merge($this->config);
            }

            Logger::comment(sprintf('Running plugin "%s"', $name));
            $class = "$name\\$name";
            $plugin = new $class($pluginConfig);
            $plugin->execute();
            Logger::notice(sprintf('Finished running plugin "%s"', $name));
        }

        Logger::notice('Done');
    }

    protected function initMagento()
    {
        require_once($this->config->getTarget() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Mage.php');
        \Mage::app();
    }
}

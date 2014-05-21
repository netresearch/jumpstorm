<?php
namespace Jumpstorm;

use Netresearch\Logger;

use Netresearch\Config;
use Netresearch\Source\SourceBase as Source;

use Jumpstorm\Extensions as Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use \Exception as Exception;

/**
 * Output a configuration value for interaction with other systems.
 *
 * @package    Jumpstorm
 * @subpackage Jumpstorm
 * @author     Kristof Ringleff <kristof@fooman.co.nz>
 */
class Display extends Magento
{
    /**
     * @see vendor/symfony/src/Symfony/Component/Console/Command/Symfony\Component\Console\Command.Command::configure()
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('display');
        $this->setDescription('Output a configuration value for interaction with other systems');
        $this->addOption('display-config',  'd', InputOption::VALUE_OPTIONAL, 'display a configuration value', '');
    }

    /**
     * @see vendor/symfony/src/Symfony/Component/Console/Command/Symfony\Component\Console\Command.Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->preExecute($input, $output);

        $method = 'get' . str_replace(' ', '', ucwords(str_replace('.', ' ', $input->getOption('display-config'))));
        if (method_exists($this->config, $method)) {
            echo $this->config->$method();
        }
    }
}

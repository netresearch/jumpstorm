<?php
namespace Jumpstorm;

use Netresearch\Logger;
use Netresearch\Config;
use Netresearch\Source\Git;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

/**
 * install extensions
 *
 * @package    Jumpstorm
 * @subpackage Jumpstorm
 * @author     Thomas Birke <thomas.birke@netresearch.de>
 */
class Base extends Command
{
    protected $config;
    protected $output;

    /**
     * @see vendor/symfony/src/Symfony/Component/Console/Command/Symfony\Component\Console\Command.Command::configure()
     */
    protected function configure()
    {
        $this->addOption('config',  'c', InputOption::VALUE_OPTIONAL, 'provide a configuration file', 'ini/jumpstorm.ini');
    }

    protected function preExecute(InputInterface $input, OutputInterface $output)
    {
        $this->config = new Config($input->getOption('config'), null, array('allowModifications' => true));
        Logger::setOutputInterface($output);
    }

    /**
     * check if target exists (try to create it otherwise) and is writeable
     * 
     * @param string $target 
     * @return string $target
     */
    protected function validateTarget($target)
    {
        if (!$target) {
            throw new \Exception('Please set common.magento.target in ini-file.');
        }
        
        if (!is_dir($target)) {
            mkdir($installPath);
        }
        
        if (!is_dir($target)) {
            throw new \Exception("Target is not a directory: $target");
        }

        if (!is_writable($target)) {
            throw new \Exception("Target directory is not writeable: $target");
        }
        
        return $target;
    }
}

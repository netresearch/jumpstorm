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
 * Install unittesting extension.
 * By now, only {@link https://github.com/IvanChepurnyi/EcomDev_PHPUnit} is supported
 *
 * @package    Jumpstorm
 * @subpackage Jumpstorm
 * @author     Thomas Birke <thomas.birke@netresearch.de>
 */
class Unittesting extends Command
{
    /**
     * @see vendor/symfony/src/Symfony/Component/Console/Command/Symfony\Component\Console\Command.Command::configure()
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('unittesting');
        $this->setDescription('Install framework for unittests and prepare test database');
    }

    /**
     * @see vendor/symfony/src/Symfony/Component/Console/Command/Symfony\Component\Console\Command.Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->preExecute($input, $output);

        // deploy unittesting framework using extensions command
        $config = $this->config->unittesting;
        $this->installExtension($config->framework, $config->extension);

        // apply some configuration
        $this->setupTestDatabase();

        Logger::notice('Done');
    }

    /**
     * Create test database and provide information for database access
     * @throws Exception
     */
    protected function setupTestDatabase()
    {
        // create database, same name as magento database, only appending '_test'
        if (false === $this->createDatabase($this->config->getDbName() . '_test')) {
            throw new Exception('Could not create test database');
        }

        // set access information in local.xml.phpunit
        $file = $this->config->getTarget() . '/app/etc/local.xml.phpunit';
        $db = sprintf('<dbname><\![CDATA[%s_test]]><\/dbname>', $this->config->getDbName());

        $cmd = sprintf('sed -i "s/<dbname>.*<\/dbname>/%s/g" %s', $db, $file);
        exec($cmd, $result, $return);

        if (0 !== $return) {
            Logger::error('Failed to set db name for unit testing framework');
        }

        // unify base url, as ecomdev/magento needs it with protocol and trailing slash given
        $baseUrl = ltrim($this->config->getMagentoBaseUrl(), 'http://');
        $baseUrl = rtrim($baseUrl, '/');
        $baseUrl = "http://$baseUrl/";
        
        $baseUrl = '<base_url>' . str_replace('/', '\\/', $baseUrl) . '<\/base_url>';
        $cmd = sprintf('sed -i "s/<base_url>.*<\/base_url>/%s/g" %s', $baseUrl, $file);
        exec($cmd, $result, $return);

        if (0 !== $return) {
            Logger::error('Failed to set base url for unit testing framework');
        }
    }
}

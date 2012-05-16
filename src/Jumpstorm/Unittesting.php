<?php
namespace Jumpstorm;

use Netresearch\Config;
use Netresearch\Source\Git;

use Jumpstorm\Extensions as Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

/**
 * install unittesting extension
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

        $this->config = new Config($input->getOption('config'), null, array('allowModifications' => true));
        $this->output = $output;

        $config = $this->config->unittesting;
        $this->installExtension($config->framework, $config->extension);

        $this->setupTestDatabase();

        $this->output->writeln('<info>Done</info>');
    }

    protected function setupTestDatabase()
    {
        $mysql = sprintf(
            'mysql -u%s -h%s',
            $this->config->getDbUser(),
            $this->config->getDbHost()
        );

        exec(sprintf(
            '%s -e \'CREATE DATABASE IF NOT EXISTS `%s`\'',
            $mysql,
            $this->config->getDbName() . '_test'
        ), $result, $return);

        if (0 !== $return) {
            throw new \Exception('Could not create test database');
        }

        $file = $this->config->getTarget() . '/app/etc/local.xml.phpunit';
        $db = sprintf('<dbname><\![CDATA[%s_test]]><\/dbname>', $this->config->getDbName());

        $cmd = sprintf('sed -i "s/<dbname>.*<\/dbname>/%s/g" %s', $db, $file);
        exec($cmd, $result, $return);

        if (0 !== $return) {
            $this->output->writeln('<error>Failed to set db name for unit testing framework</error>');
        }

        $baseUrl = '<base_url>' . str_replace('/', '\\/', $this->config->getMagentoBaseUrl()) . '<\/base_url>';
        $cmd = sprintf('sed -i "s/<base_url>.*<\/base_url>/%s/g" %s', $baseUrl, $file);
        exec($cmd, $result, $return);

        if (0 !== $return) {
            $this->output->writeln('<error>Failed to set base url for unit testing framework</error>');
        }
    }
}

<?php
/**
 * Magento environment builder for jenkins, demo hosts and developers
 *
 * Known issues:
 * 
 * Prio 1:
 * - Path of ini-file can not be specified as param on CLI (!!!)
 *
 * Prio 2:
 * - Clone from gitorious is not working
 * - installPath can not create folder recursivly
 *
 */

class Unittesting
{
    /* stdClass */
    private $config;

    /* Git */
    private $git;
    
    private $supportedFiletypes = array('sh', 'php');

    public function __construct($configpath = null)
    {
        // Parse ini file
        $this->config = new Config($configpath, null, array('allowModifications' => true));
    }

    public function run()
    {
        Logger::notice('Preparing unit testing framework');

        $extensionfolder = $this->config->getInstallPath() . DIRECTORY_SEPARATOR . '.modman';
        $cmd = sprintf('git clone https://github.com/IvanChepurnyi/EcomDev_PHPUnit.git %s/ecomdev', $extensionfolder);
        exec($cmd, $result, $return);
        
        $cmd = sprintf('cd %s && modman deploy ecomdev', $extensionfolder);
        exec($cmd, $result, $return);

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
            throw new Exception('Could not create test database');
        }

        $file = $this->config->getInstallPath() . '/app/etc/local.xml.phpunit';
        $db = sprintf('<dbname><\![CDATA[%s_test]]><\/dbname>', $this->config->getDbName());

        $cmd = sprintf('sed -i "s/<dbname>.*<\/dbname>/%s/g" %s', $db, $file);
        exec($cmd, $result, $return);

        if (0 !== $return) {
            Logger::error('Failed to set db name for unit testing framework');
        }

        $baseUrl = '<base_url>' . str_replace('/', '\\/', $this->config->getMagentoBaseUrl()) . '<\/base_url>';
        $cmd = sprintf('sed -i "s/<base_url>.*<\/base_url>/%s/g" %s', $baseUrl, $file);
        exec($cmd, $result, $return);

        if (0 !== $return) {
            Logger::error('Failed to set base url for unit testing framework');
        }

        Logger::notice('Done');
    }

    private function createExtensionfolder()
    {
        $extensionfolder = $this->config->getInstallPath() . DIRECTORY_SEPARATOR . '.modman';

        if (!is_dir($extensionfolder)) {
            Logger::notice('Creating extension folder ' . $extensionfolder);
            mkdir($extensionfolder);
        }

        return $extensionfolder;
    }
}

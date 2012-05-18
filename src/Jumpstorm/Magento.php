<?php
namespace Jumpstorm;

use Netresearch\Logger;

use Netresearch\Config;
use Netresearch\Source\Base as Source;
use Netresearch\Source\Git;
use Netresearch\Source\Filesystem;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

/**
 * Setup Magento
 *
 * @package    Jumpstorm
 * @subpackage Jumpstorm
 * @author     Thomas Birke <thomas.birke@netresearch.de>
 */
class Magento extends Base
{
    /**
     * @see vendor/symfony/src/Symfony/Component/Console/Command/Symfony\Component\Console\Command.Command::configure()
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('magento');
    }
    
    protected function prepareMysqlCommand()
    {
        $mysql = sprintf(
                'mysql -u%s -h%s',
                $this->config->getDbUser(),
                $this->config->getDbHost()
        );
        
        // prepare mysql command: password
        if (!is_null($this->config->getDbPass())) {
            $mysql .= sprintf(' -p%s', $this->config->getDbPass());
        }
        
        return $mysql;
    }
    
    protected function createDatabase()
    {
        // prepare mysql command: user, host and password
        $mysql = $this->prepareMysqlCommand();
        
        // recreate database if it already exists
        Logger::log('Creating database %s', array($this->config->getDbName()));

        exec(sprintf(
            '%s -e \'DROP DATABASE IF EXISTS `%s`\'',
            $mysql,
            $this->config->getDbName()
        ), $result, $return);
        
        exec(sprintf(
            '%s -e \'CREATE DATABASE IF NOT EXISTS `%s`\'',
            $mysql,
            $this->config->getDbName()
        ), $result, $return);

        if (0 !== $return) {
            throw new \Exception('Could not create live database');
        }
    }

    /**
     * Assuming that we copied another folder into the target directory,
     * we move all files one level up.
     * 
     * @param string $target The install path (docroot)
     * @param string $root The top level source directory
     */
    protected function moveToDocroot($target, $root = 'magento')
    {
        $fileRoot = $target . DIRECTORY_SEPARATOR . $root;
        $fileTest = $fileRoot . DIRECTORY_SEPARATOR . '.htaccess';
        
        if (file_exists($fileRoot) && is_dir($fileRoot) && file_exists($fileTest)) {
            // move hidden file to docroot
            exec(sprintf('mv %s %s', $fileTest, $target));
            // move all the rest to docroot
            exec(sprintf('mv %s %s', $fileRoot . DIRECTORY_SEPARATOR . '*', $target));
            // delete the now empty source directory
            exec(sprintf('rmdir %s %s', $fileRoot));
        }
    }
    
    protected function installMagento($source, $target)
    {
        $sourceModel = Source::getSourceModel($source);
        
        // copy from source to install directory
        $sourceModel->copy($source, $target);
    }
    
    protected function installSampledata($source, $target)
    {
        // glob for sql file in $source
        $files = glob($source . DIRECTORY_SEPARATOR . '*.sql');
        if (false === $files || count($files) !== 1) {
            throw new Exception("Could not detect sample data sql file in source directory $source");
        }
        $sampledataSql = $files[0];

        // create empty database with credentials from ini file
        $this->createDatabase();

        // prepare mysql command: user, host and password
        $mysql = $this->prepareMysqlCommand();
        
        // insert sample data to database
        exec(sprintf(
            '%s %s < %s',
            $mysql,
            $this->config->getDbName(),
            $sampledataSql 
        ), $result, $return);

        if (0 !== $return) {
            throw new Exception('Could not import sample data into database');
        }

        // copy sample data images
        $sourceMediaDir = $source . DIRECTORY_SEPARATOR . 'media';
        $targetMediaDir = $target . DIRECTORY_SEPARATOR . 'media';
        $sourceModel = Source::getSourceModel($sourceMediaDir);
        $sourceModel->copy($targetMediaDir);
    }

    /**
     * @see vendor/symfony/src/Symfony/Component/Console/Command/Symfony\Component\Console\Command.Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->preExecute($input, $output);

        // set the path where magento should get installed
        $target = $this->validateTarget($this->config->getTarget());

        // empty target directory if it already exists 
        if (file_exists($target)) {
            Logger::log('Delete existing Magento at %s', array($target));
            exec(sprintf('rm -rf %s/*', $target));
            exec(sprintf('rm -rf %s/.[a-zA-Z0-9]*', $target));
        }
        
        // set the source where magento should get retrieved from
        $source = $this->config->getMagentoSource();
        // copy files from source to target
        $this->installMagento($source, $target);
        // move installed files to docroot
        $this->moveToDocroot($target, 'htdocs');
        $this->moveToDocroot($target, 'magento');
        
        // install sample data
        if (null !== $this->config->getMagentoSampledataSource()) {
            $this->installSampledata(
                $this->config->getMagentoSampledataSource(),
                $target
            );
        }

        // run install.php
        $this->runMageScript($target);

        // clean cache
        exec(sprintf('rm -rf %s/var/cache/*', $target));

        Logger::notice('Done');
    }

    private function setPermissions($target)
    {
        $exec = sprintf('chmod -R 0777 %s/app/etc %s/var/ %s/media/', $target, $target, $target);
        exec($exec, $result, $return);

        if (0 !== $return) {
            throw new Exception('Could not set permissions for folders app/etc, var and media');
        }
    }

    protected function runMageScript($target)
    {
        $this->setPermissions($target);
        if (file_exists($target . '/app/etc/local.xml')) {
            unlink($target . '/app/etc/local.xml');
        }

        $this->createDatabase();

        $cmd = sprintf('php %s%sinstall.php -- ', $target, DIRECTORY_SEPARATOR);
        $cmd .= implode(' ', array(
            '--license_agreement_accepted "yes"',
            '--locale "de_DE"',
            '--timezone "Europe/Berlin"',
            '--default_currency "EUR"',
            '--db_host "' . $this->config->getDbHost() . '"',
            '--db_name "' . $this->config->getDbName() . '"',
            '--db_user "' . $this->config->getDbUser() . '"',
            '--db_pass "' . $this->config->getDbPass() . '"',
            '--db_prefix "' . $this->config->getDbPrefix() . '"',
            '--session_save "files"',
            '--admin_frontend "admin"',
            '--url "' . $this->config->getMagentoBaseUrl() . '"',
            '--skip_url_validation',
            '--use_rewrites "yes"',
            '--use_secure "no"',
            '--secure_base_url "' . $this->config->getMagentoBaseUrl() . '"',
            '--use_secure_admin "no"',
            '--admin_firstname "' . $this->config->getAdminFirstname() . '"',
            '--admin_lastname "' . $this->config->getAdminLastname() . '"',
            '--admin_email "' . $this->config->getAdminEmail() . '"',
            '--admin_username "' . $this->config->getAdminUser() . '"',
            '--admin_password "' . $this->config->getAdminPass() . '"',
        ));

        exec($cmd, $result, $return);

        if (0 !== $return) {
            throw new Exception(sprintf('Installation via install.php failed, result: %s', implode(PHP_EOL . '    ', $result)));
        }

        // reindexing data
        $cmd = sprintf('php %s%sshell/indexer.php reindexall', $this->config->getInstallPath(), DIRECTORY_SEPARATOR);
        exec($cmd, $result, $return);

        if (0 !== $return) {
            throw new Exception('Failed to rebuild index');
        }

        $this->setPermissions($target);
    }
}

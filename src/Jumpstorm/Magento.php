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

use \Exception as Exception;

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
        $this->setDescription('Install Magento');
    }

    /**
     * Assuming that we cloned another folder into the target directory,
     * we move all files one level up.
     *
     * @param string $target The install path (docroot)
     * @param string $root The name of the directory where Magento's index.php resides in
     */
    protected function moveToDocroot($target, $root = 'magento')
    {
        $fileRoot = $target . DIRECTORY_SEPARATOR . $root;
        $fileTest = $fileRoot . DIRECTORY_SEPARATOR . '.htaccess';

        if (file_exists($fileRoot) && is_dir($fileRoot) && file_exists($fileTest)) {
            // move files to docroot
            exec(sprintf(
                'mv %s %s %s %s',
                $fileRoot . DIRECTORY_SEPARATOR . '*', // regular files
                $fileRoot . DIRECTORY_SEPARATOR . '.htaccess', // hidden files
                $fileRoot . DIRECTORY_SEPARATOR . '.htaccess.sample', // hidden files
                $target
            ));

            // delete the now empty source directory
            if (is_link($fileRoot)) {
                // symlink
                exec(sprintf('rm %s', $fileRoot));
            } elseif (is_dir($fileRoot)) {
                // regular empty directory
                exec(sprintf('rmdir %s', $fileRoot));
            }
        }
    }

    /**
     * Copy Magento files from source to target directory
     * @param string $source Absolute directory name or repository
     * @param string $target Absolute directory name (Magento root)
     * @param string $branch Branch identifier in case of repository checkout
     */
    protected function installMagento($source, $target, $branch)
    {
        $sourceModel = Source::getSourceModel($source);
        // copy from source to install directory
        $sourceModel->copy($target, $branch);
    }

    /**
     * Install Magento Sample Data, including db tables and media files
     * @param string $source Absolute directory name or repository
     * @param string $target Absolute directory name (Magento root)
     * @throws Exception
     */
    protected function installSampledata($source, $target, $branch)
    {
        $sampleDataDir = $target . DIRECTORY_SEPARATOR . 'sampleData';

        $sourceModel = Source::getSourceModel($source);
        // copy from source to install directory
        $sourceModel->copy($sampleDataDir, $branch);

        // glob for sql file in $source
        $files = glob($sampleDataDir . DIRECTORY_SEPARATOR . '*.sql');
        if (false === $files || count($files) !== 1) {
            throw new Exception("Could not detect sample data sql file in source directory $sampleDataDir");
        }
        $sampledataSql = $files[0];
        Logger::log("Importing sample data from $sampledataSql");

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
        Logger::log("Copying sample data media files");
        $sourceMediaDir = $sampleDataDir . DIRECTORY_SEPARATOR . 'media';
        $targetMediaDir = $target . DIRECTORY_SEPARATOR . 'media';
        $sourceModel = Source::getSourceModel($sourceMediaDir);
        $sourceModel->copy($targetMediaDir);

        // remove temporary sample data folder
        exec(sprintf('rm -rf %s', $sampleDataDir));
    }

    /**
     * Take a snapshot of the current database and local.xml config
     * @param string $backupLocation Absolute directory name for backup location
     * @param string $magentoLocation Absolute directory of Magento installation
     * @throws Exception
     */
    protected function createDatabaseSnapshot($backupLocation, $magentoLocation)
    {

        //remove existing backups with the same config
        $localXmlBackupFile = $this->getBackupFilename($backupLocation, true);
        if (file_exists($localXmlBackupFile)) {
            unlink($localXmlBackupFile);
        }

        $dbBackupFile = $this->getBackupFilename($backupLocation);
        if (file_exists($dbBackupFile)) {
            unlink($dbBackupFile);
        }

        // prepare mysqldump command: user, host and password
        $mysql = $this->prepareMysqlCommand(true);
        
        // run database backup
        exec(
            sprintf(
                '%s %s > %s',
                $mysql,
                $this->config->getDbName(),
                $dbBackupFile
            ), $result, $return
        );

        if (0 !== $return) {
            throw new Exception('Could not create database snapshot');
        }

        // copy local.xml file
        $localXmlFile = $magentoLocation . '/app/etc/local.xml';
        copy($localXmlFile, $localXmlBackupFile);
    }

    /**
     * Install Magento from previous database snapshot
     * @param $target Absolute directory of Magento installation
     *
     * @return bool
     */
    protected function installedFromDbSnapshot($target)
    {

        $backupLocation = $this->config->getBackupTarget();
        if (!$backupLocation) {
            return false;
        }

        // check for local xml backup
        $localXmlBackupFile = $this->getBackupFilename($backupLocation, true);
        if (!file_exists($localXmlBackupFile)) {
            Logger::comment('No existing backup xml file at %s', array($localXmlBackupFile));
            return false;
        }

        // check for database backup
        $dbBackupFile = $this->getBackupFilename($backupLocation);
        if (!file_exists($dbBackupFile)) {
            Logger::comment('No existing backup db file at %s', array($dbBackupFile));
            return false;
        }

        // prepare mysql command: user, host and password
        $mysql = $this->prepareMysqlCommand();

        // insert sample data to database
        exec(
            sprintf(
                '%s %s < %s',
                $mysql,
                $this->config->getDbName(),
                $dbBackupFile
            ), $result, $return
        );

        if (0 !== $return) {
            Logger::comment('Failed to restored from database backup file %s', array($localXmlBackupFile));
            return false;
        }

        // copy local.xml file
        $localXmlFile = $target . '/app/etc/local.xml';
        copy($localXmlBackupFile, $localXmlFile);

        Logger::success('Installed from previous snapshot');
        return true;
    }

    /**
     * Convert baseUrl into a usable filename
     * @return string
     */
    protected function getBaseUrlFileReference()
    {
        $search = array('http://','https://',DIRECTORY_SEPARATOR);
        $replace = array('','','_');
        return str_replace(
            $search,
            $replace,
            $this->config->getMagentoBaseUrl()
        );
    }

    /**
     * retrieve backup file names
     * @param      $backupLocation
     * @param bool $xml if true return name of the local.xml file else sql
     *
     * @return string
     */
    protected function getBackupFilename($backupLocation, $xml = false)
    {
        $format = $xml ? '%s%s%s.local.xml' : '%s%s%s.sql';
        return sprintf(
            $format,
            $backupLocation,
            DIRECTORY_SEPARATOR,
            $this->getBaseUrlFileReference()
        );
    }

    /**
     * Set permissions for web server access
     *
     * @param string $target Absolute directory name (Magento root)
     * @throws Exception
     */
    protected function setPermissions($target)
    {
        $exec = sprintf('chmod -R 0777 %s/app/etc %s/var/ %s/media/', $target, $target, $target);
        exec($exec, $result, $return);

        if (0 !== $return) {
            throw new Exception('Could not set permissions for folders app/etc, var and media');
        }
    }

    /**
     * Execute Magento's install.php
     *
     * @param string $target Absolute directory name (Magento root)
     * @throws Exception
     */
    protected function runMageScript($target)
    {
        Logger::log("Executing installation via install.php");

        $this->setPermissions($target);
        if (file_exists($target . '/app/etc/local.xml')) {
            unlink($target . '/app/etc/local.xml');
        }

        // avoid access during installation
        touch($target . '/maintenance.flag');

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
        if($this->config->getEncryptionKey()){
            $cmd .= ' --encryption_key "' . $this->config->getEncryptionKey() . '"';
        }

        exec($cmd, $result, $return);

        if (0 !== $return) {
            throw new Exception(sprintf('Installation via install.php failed, result: %s', implode(PHP_EOL . '    ', $result)));
        }

        // reindexing data
        if (file_exists(sprintf('%s%sshell/indexer.php', $target, DIRECTORY_SEPARATOR))) {
            $cmd = sprintf('php %s%sshell/indexer.php reindexall', $target, DIRECTORY_SEPARATOR);
            exec($cmd, $result, $return);

            if (0 !== $return) {
                throw new Exception('Failed to rebuild index');
            }
        } else {
            Logger::comment('Could not find indexer at %s/shell/indexer.php, but its existance depends on Magento version', array($target));
        }

        $this->setPermissions($target);

        unlink($target . '/maintenance.flag');
    }

    protected function fixInstallConfig($target)
    {
        $path = $target . '/app/code/core/Mage/Install/etc/config.xml';
        exec(sprintf('sed "s/<pdo_mysql\/>/<pdo_mysql>1<\/pdo_mysql>/" %s > %s.fixed && mv %s.fixed %s', $path, $path, $path, $path));
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
            Logger::comment('Delete existing Magento at %s', array($target));
            exec(sprintf('rm -rf %s/*', $target));
            exec(sprintf('rm -rf %s/.[a-zA-Z0-9]*', $target));
        }

        // set the source where magento should get retrieved from
        $source = $this->config->getMagentoSource();
        // copy files from source to target
        $this->installMagento($source, $target, $this->config->getMagentoBranch());
        // move installed files to docroot
        $this->moveToDocroot($target, 'htdocs');
        $this->moveToDocroot($target, 'magento');

        Logger::success('Fetched '. $this->config->getMagentoVersion().' Magento sources');

        // create empty database with credentials from ini file
        if (false === $this->createDatabase($this->config->getDbName())) {
            throw new Exception('Could not create live database');
        }

        if (!$this->installedFromDbSnapshot($target)) {
            // install sample data
            if (null !== $this->config->getMagentoSampledataSource()) {
                $this->installSampledata(
                    $this->config->getMagentoSampledataSource(),
                    $target,
                    $this->config->getMagentoSampledataBranch()
                );
                Logger::success('Installed sample data');
            }

            // avoid exception 'PHP Extensions "0" must be loaded.'
            $this->fixInstallConfig($target);

            // run install.php
            $this->runMageScript($target);

            // create database backup directory
            if (null !== $this->config->getBackupTarget()) {
                $this->createDatabaseSnapshot($this->config->getBackupTarget(), $target);
                Logger::success('Created Database Backup');
            }
        }
        // clean cache
        exec(sprintf('rm -rf %s/var/cache/*', $target));

        Logger::success('Finished Magento installation');
    }
}

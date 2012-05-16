<?php
namespace Jumpstorm;

use Netresearch\Config;
use Netresearch\Source\Git;

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
class Magento extends Command
{
    const ASSETS_URL = 'http://www.magentocommerce.com/downloads/assets/';

    const SAMPLEDATA_SQL = 'magento_sample_data_for_1.2.0.sql';

    /* stdClass */
    private $config;

    /* Git */
    private $git;
    
    private $supportedFiletypes = array('sh', 'php');

    /**
     * @see vendor/symfony/src/Symfony/Component/Console/Command/Symfony\Component\Console\Command.Command::configure()
     */
    protected function configure()
    {
        $mode = InputOption::VALUE_REQUIRED;
        $this
            ->setName('magento')
            ->addOption('config',  'c', InputOption::VALUE_OPTIONAL, 'provide a configuration file')
            ->addOption('branch',  'b', InputOption::VALUE_OPTIONAL, 'branch of Magento');
    }

    /**
     * @see vendor/symfony/src/Symfony/Component/Console/Command/Symfony\Component\Console\Command.Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->config = new Config($input->getOption('config'), null, array('allowModifications' => true));

        $branch = $input->getOption(
            'branch',
            (is_null($this->config->getMagentoCheckout())) ? 'master' : $this->config->getMagentoCheckout()
        );

        $path = $this->config->getMagentoPath();

        if (strlen($this->config->getInstallPath()) && file_exists($this->config->getInstallPath())) {
            $output->writeln(sprintf(
                '<comment>Delete existing Magento at %s</comment>',
                $this->config->getInstallPath()
            ));
            exec(sprintf('rm -rf %s/*', $this->config->getInstallPath()));
            exec(sprintf('rm -rf %s/.[a-zA-Z0-9]*', $this->config->getInstallPath()));
        }

        // Create magento folder and run magento install process
        if (Git::isRepo($path)) {
            $this->git = new Git($path);

            try {
                $output->writeln('<comment>Cloning Git repo</comment>');
                $this->git->clonerepo($this->config->getInstallPath());

                if (is_null($branch)) {
                }
                $output->writeln("<comment>Git checkout $branch<comment>");
                $this->git->checkout($this->config->getInstallPath(), $branch);
            } catch (Exception $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
            }
        } else {
            if (!$this->copyMagentoFiles()) {
                $output->writeln('<error>Could not copy magento files to desired location</error>');
            }
        }
        if (file_exists($this->config->getInstallPath() . '/htdocs')
            && is_dir($this->config->getInstallPath() . '/htdocs')
            && file_exists($this->config->getInstallPath() . '/htdocs/.htaccess')
        ) {
            exec(sprintf('mv %s %s', $this->config->getInstallPath() . '/htdocs/.htaccess', $this->config->getInstallPath()));
            exec(sprintf('mv %s %s', $this->config->getInstallPath() . '/htdocs/*', $this->config->getInstallPath()));
        }
        if (file_exists($this->config->getInstallPath() . '/magento')
            && is_dir($this->config->getInstallPath() . '/magento')
            && file_exists($this->config->getInstallPath() . '/magento/.htaccess')
        ) {
            exec(sprintf('mv %s %s', $this->config->getInstallPath() . '/magento/.htaccess', $this->config->getInstallPath()));
            exec(sprintf('mv %s %s', $this->config->getInstallPath() . '/magento/*', $this->config->getInstallPath()));
        }

        if (false !== $this->config->getMagentoSampleDataVersion()) {
            $this->installSampleData($this->config->getMagentoSampleDataVersion());
        }

        $this->runMageScript($output);

        exec(sprintf('rm -rf %s/var/cache/*', $this->config->getInstallPath()));
        exec(sprintf('cd %s && modman init && cd -', $this->config->getInstallPath()));

        $output->writeln('<notice>Done</notice>');
    }

    protected function createDatabase($output)
    {
        $mysql = sprintf(
            'mysql -u%s -h%s',
            $this->config->getDbUser(),
            $this->config->getDbHost()
        );

        // create new database
        $output->writeln(sprintf(
            '<comment>Creating database %s</comment>',
            $this->config->getDbName()
        ));

        exec(sprintf(
            '%s -e \'CREATE DATABASE IF NOT EXISTS `%s`\'',
            $mysql,
            $this->config->getDbName()
        ), $result, $return);

        if (0 !== $return) {
            throw new \Exception('Could not create live database');
        }
    }


    private function copyMagentoFiles()
    {
        $installPath = $this->config->getInstallPath();

        if (!is_dir($installPath)) {
            //Logger::notice('Creating destination folder');
            mkdir($installPath);
        }

        //Logger::notice('Copying magento files to ' . $installPath);

        $command = sprintf('rsync -a -h --exclude="var/*" --exclude="*.git" %s %s 2>&1', $this->config->getMagentoPath(), $installPath);
        exec($command, $result, $return);

        return (0 === $return);
    }

    private function installSampleData($version = '1.2.0')
    {
        $sampleDataFile = "magento-sample-data-$version.tar.gz";
        $sampleDataFolder = str_replace('.tar.gz', '', $sampleDataFile) . DIRECTORY_SEPARATOR;
        $sampledataSql = $sampleDataFolder . "magento_sample_data_for_$version.sql";
        
        // drop database, if it already exists
        $mysql = sprintf(
            'mysql -u%s -h%s',
            $this->config->getDbUser(),
            $this->config->getDbHost()
        );

        if (!is_null($this->config->getDbPass()) && '' !== $this->config->getDbPass()) {
            $mysql .= sprintf(' -p%s', $this->config->getDbPass());
        }

        exec(sprintf($mysql . ' -e \'show databases like "%s"\' | wc -l', $this->config->getDbName()), $result, $return);
        if (0 < (int) $result[0]) {
            //Logger::notice('Drop existing database ' . $this->config->getDbName());
            exec(sprintf($mysql . ' -e \'drop database `%s`\'', $this->config->getDbName()), $result, $return);
            if (0 !== $return) {
                throw new Exception('Could not drop old database ' . $this->config->getDbName());
            }
        }
        
        // download sample data
        $this->downloadSampleData($version);

        // insert sample data to database
        //Logger::notice('Importing sample data from file ' . $sampledataSql);

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
        $installPath = $this->config->getInstallPath();
        exec(sprintf('cp -R %s/media/* %s/media/ 2>&1', $sampleDataFolder, $installPath), $result, $return);

        if (0 !== $return) {
            throw new Exception('Could not copy sample data images');
        }

        // remove downloaded sample data
        exec(sprintf('rm -rf %s', $sampleDataFolder));
        unlink($sampleDataFile);
    }

    private function downloadSampleData($version)
    {
        $sampleDataFile = "magento-sample-data-$version.tar.gz";
        $sampleDataUrl = self::ASSETS_URL . $version . '/';
        
        exec(sprintf('wget %s%s 2>&1', $sampleDataUrl, $sampleDataFile), $result, $return);

        if (0 !== $return) {
            throw new Exception('Could not download sample data');
        }

        exec(sprintf('tar -zxvf %s 2>&1', $sampleDataFile), $result, $return);

        if (0 !== $return) {
            throw new Exception('Unable to unpack sample data');
        }
    }

    private function setPermissions($installPath)
    {
        //Logger::notice('Setting file permissions');
        $exec = sprintf('chmod -R 0777 %s/app/etc %s/var/ %s/media/', $installPath, $installPath, $installPath);
        exec($exec, $result, $return);

        if (0 !== $return) {
            throw new Exception('Could not set permissions for folders app/etc, var and media');
        }
    }

    private function runMageScript($output)
    {
        $this->setPermissions($this->config->getInstallPath());
        if (file_exists($this->config->getInstallPath() . '/app/etc/local.xml')) {
            unlink($this->config->getInstallPath() . '/app/etc/local.xml');
        }

        $this->createDatabase($output);

        //Logger::notice('Installing magento via install.php');
        //Logger::notice('This could take a few minutes... Or more...');

        $cmd = sprintf('php %s%sinstall.php -- ', $this->config->getInstallPath(), DIRECTORY_SEPARATOR);
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

        //Logger::notice('Reindexing data');

        $cmd = sprintf('php %s%sshell/indexer.php reindexall', $this->config->getInstallPath(), DIRECTORY_SEPARATOR);
        exec($cmd, $result, $return);

        if (0 !== $return) {
            throw new Exception('Failed to rebuild index');
        }

        $this->setPermissions($this->config->getInstallPath());
    }
}

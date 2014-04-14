<?php
namespace Jumpstorm;

use Netresearch\Source\Git;
use Netresearch\Source\MagentoConnect;

use Netresearch\Logger;

use Netresearch\Config;
use Netresearch\Config\Base as BaseConfig;
use Netresearch\Modman;
use Netresearch\Source\Base as Source;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use \Exception as Exception;

/**
 * Install extensions in a two step process: copy all extension files to a
 * central directory, then deploy into the Magento installation.
 *
 * @package    Jumpstorm
 * @subpackage Jumpstorm
 * @author     Thomas Birke <thomas.birke@netresearch.de>
 * @author     Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class Extensions extends Base
{
    protected $modman;

    /**
     * Magento target directory
     * @var string
     */
    protected $magentoRoot;
    /**
     * Temporary root directory for all extensions to be installed
     * @var string
     */
    protected $extensionRootDir;
    /**
     * Temporary directory for current extension
     * @var string
     */
    protected $extensionDir;
    /**
     * Flag to indicate whether modman may be used or not
     * @var boolean
     */
    protected $useModman = true;

    /**
     * @see vendor/symfony/src/Symfony/Component/Console/Command/Symfony\Component\Console\Command.Command::configure()
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('extensions');
        $this->setDescription('Install extensions');
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function preExecute(InputInterface $input, OutputInterface $output)
    {
        parent::preExecute($input, $output);

        // check if modman is installed
        exec('modman --version', $output, $return);
        if ($return === 127) {
            $this->useModman = false;
        }

        // create extension root directory if necessary
        $this->magentoRoot = $this->validateTarget($this->config->getTarget());
        $extensionRootDir = $this->magentoRoot . DIRECTORY_SEPARATOR . '.modman';
        if (!is_dir($extensionRootDir)) {
            Logger::log('Creating extension root directory %s', array($extensionRootDir));
            mkdir($extensionRootDir);
        }
        $this->extensionRootDir = $extensionRootDir;
    }


    /**
     * @see vendor/symfony/src/Symfony/Component/Console/Command/Symfony\Component\Console\Command.Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->preExecute($input, $output);

        // iterate extensions and perform installation
        foreach ($this->config->getExtensions() as $alias => $extension) {
            $this->installExtension($alias, $extension);
        }

        $this->initMagento();
        \Mage_Core_Model_Resource_Setup::applyAllUpdates();
        \Mage_Core_Model_Resource_Setup::applyAllDataUpdates();
        \Mage::getModel('core/cache')->flush();

        Logger::notice('Done');
    }

    /**
     * install extension
     *
     * @param string $alias
     * @param BaseConfig $extension
     * @return void
     */
    protected function installExtension($alias, BaseConfig $extension)
    {
        Logger::log('Installing extension %s from %s', array($alias, $extension->source));
        $this->extensionDir = $this->extensionRootDir . DIRECTORY_SEPARATOR . $alias;

        // cleanup modman directory
        exec('rm -rf ' . $this->extensionDir);
        if ($this->useModman) {
            exec(sprintf('cd %s; modman clean', $this->magentoRoot));
        }

        // copy files to modman directory
        $sourceModel = Source::getSourceModel($extension->source);
        if ($sourceModel instanceof MagentoConnect) {
            $sourceModel->setMagentoRoot($this->magentoRoot);
        } elseif ($sourceModel instanceof Git) {
            $sourceModel->setCloneRecursive((bool)$extension->recursive);
        }
        $sourceModel->copy($this->extensionDir, $extension->branch);

        // deploy files to target directory
        $this->deployExtension($alias);

        Logger::notice('Installed extension %s', array($alias));
    }

    protected function getModman()
    {
        if (is_null($this->modman)) {
            $this->modman = new Modman();
            $this->modman->setRoot($this->config->getTarget());
        }
        return $this->modman;
    }

    /**
     * Locate a modman file within the extension directory.
     *
     * @return string Absolute path to modman file if available, empty string otherwise
     */
    protected function locateModmanFile()
    {
        if (!$this->useModman) {
            return '';
        }

        $modmanFile = $this->extensionDir . DIRECTORY_SEPARATOR . 'modman';
        if (file_exists($modmanFile)) {
            return $modmanFile;
        }

        $pattern = $this->extensionDir . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'modman';
        foreach (glob($pattern) as $modmanFile) {
            return $modmanFile;
        }

        return '';
    }

    /**
     * Sync extension files from .modman to target directories
     *
     * @param string $alias
     */
    protected function deployExtension($alias)
    {
        // detect location of modman file, if available
        $modmanFile = $this->locateModmanFile();
        if ($modmanFile) {
            // if modman file was found, deploy via modman

            // alias may have to be changed to subdirectory
            $pattern = sprintf("|^%s/(.+)/%s$|", $this->extensionRootDir, 'modman');
            preg_match($pattern, $modmanFile, $matches);
            $alias = $matches[1];

            Logger::log('Deploying extension %s via modman', array($alias));
            $command = sprintf(
                'cd %s; modman deploy %s --force',
                $this->magentoRoot,
                $alias
            );
            exec($command, $result, $return);
        } else {
            // deploy via rsync otherwise
            Logger::log('Deploying extension %s via rsync', array($alias));
            $command = sprintf(
                'rsync -a -h --exclude="doc/*" --exclude="*.git" %s %s 2>&1',
                $this->extensionDir . DIRECTORY_SEPARATOR,
                $this->magentoRoot
            );
            exec($command, $result, $return);
        }

        if (0 !== $return) {
            throw new Exception("Could not deploy extension $alias");
        }
    }

    /**
     * Extension files are installed to .modman directory before deployment,
     * so create that directory if necessary
     *
     * @return string Absolute path to extension directory
     */
    private function createExtensionFolder()
    {
        $this->validateTarget($this->config->getTarget());

        $folder = $this->getExtensionFolder();

        if (!is_dir($folder)) {
            $return = $this->getModman()->call('init');
        }

        return $folder;
    }

    /**
     * Obtain the name of the directory where all extensions get initially
     * installed to before deployment. Currently '.modman'
     * 
     * @return string Absolute path to extension directory
     */
    protected function getExtensionFolder()
    {
        return $this->config->getTarget() . DIRECTORY_SEPARATOR . '.modman';
    }
}

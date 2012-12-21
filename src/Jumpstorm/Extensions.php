<?php
namespace Jumpstorm;

use Netresearch\Logger;

use Netresearch\Config;
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
 * install extensions
 *
 * @package    Jumpstorm
 * @subpackage Jumpstorm
 * @author     Thomas Birke <thomas.birke@netresearch.de>
 */
class Extensions extends Base
{
    protected $modman;

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
     * @see vendor/symfony/src/Symfony/Component/Console/Command/Symfony\Component\Console\Command.Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->preExecute($input, $output);

        $this->createExtensionFolder();

        foreach ($this->config->getExtensions() as $name=>$extension) {
            $this->installExtension($name, $extension);
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
     * @param string $name
     * @param object $extension
     * @return void
     */
    protected function installExtension($name, $extension)
    {
        Logger::log('Installing extension %s from %s', array(
            $name,
            $extension->source
        ));
        $this->removeLegacyFiles($name);

        // copy from source to install directory
        $sourceModel = Source::getSourceModel($extension->source, $this->config->getTarget());
        $sourceModel->copy($this->getExtensionFolder() . DIRECTORY_SEPARATOR . $name, $extension->branch);

        $this->deployExtension($name);
        Logger::notice('Installed extension %s', array($name));
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
     * remove extension from Magento modman dir, if it is already installed
     *
     * @param mixed $name Extension identifier
     * @return void
     */
    protected function removeLegacyFiles($name)
    {
        $path = $this->getExtensionFolder() . DIRECTORY_SEPARATOR . $name;
        passthru("rm -rf $path");
        $this->getModman()->call('clean');
    }

    /**
     * Sync extension files from .modman to target directories
     * @param string $name The name of the extension
     * @throws Exception
     */
    protected function deployExtension($name)
    {
        $source = $this->getExtensionFolder() . DIRECTORY_SEPARATOR . $name;
        if (false == file_exists($source . DIRECTORY_SEPARATOR . 'modman')) {
            $deployed = false;
            foreach (glob($source . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'modman') as $modmanFile) {
                $subSource = substr($modmanFile, strlen($source . DIRECTORY_SEPARATOR), - strlen(DIRECTORY_SEPARATOR . 'modman'));
                $this->deployExtension($name . DIRECTORY_SEPARATOR . $subSource);
                $deployed = true;
            }
            if ($deployed) {
                return;
            }
        }
        $baseTarget = $this->config->getTarget();
        if (file_exists($source . '/modman')) {
            $return = $this->getModman()->call("deploy $name --force");
        } else {
            Logger::log('Copy extension from %s', array($source));
            $command = sprintf(
                'rsync -a -h --exclude="doc/*" --exclude="*.git" %s %s 2>&1',
                $source . DIRECTORY_SEPARATOR,
                $baseTarget
            );
            exec($command, $result, $return);
        }

        if (0 !== $return) {
            throw new Exception("Could not deploy extension $name");
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

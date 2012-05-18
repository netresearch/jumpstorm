<?php
namespace Jumpstorm;

use Netresearch\Logger;

use Netresearch\Config;
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
        // copy from source to install directory
        $sourceModel = Source::getSourceModel($extension->source);
        $sourceModel->copy($this->getExtensionFolder() . DIRECTORY_SEPARATOR . $name, $extension->branch);

        $this->deployExtension($name);
        Logger::notice('Installed extension %s', array($name));
    }

    /**
     * Sync extension files from .modman to target directories
     * @param string $name The name of the extension
     * @throws Exception
     */
    protected function deployExtension($name)
    {
        $source = $this->getExtensionFolder() . DIRECTORY_SEPARATOR . $name;
        $command = sprintf(
            'rsync -a -h --exclude="doc/*" --exclude="*.git" %s %s 2>&1',
            $source . DIRECTORY_SEPARATOR,
            $this->config->getTarget()
        );
        exec($command, $result, $return);

        if (0 !== $return) {
            throw new Exception("Could not copy extension $name");
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
            Logger::log('Creating extension folder %s', array($folder));
            mkdir($folder);
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

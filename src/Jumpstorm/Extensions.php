<?php
namespace Jumpstorm;

use Netresearch\Config;
use Netresearch\Source\Git;

use Jumpstorm\Base as Command;
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
class Extensions extends Command
{
    /**
     * @see vendor/symfony/src/Symfony/Component/Console/Command/Symfony\Component\Console\Command.Command::configure()
     */
    protected function configure()
    {
        $this->setName('extensions');
        parent::configure();
    }

    /**
     * @see vendor/symfony/src/Symfony/Component/Console/Command/Symfony\Component\Console\Command.Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        foreach ($this->config->getExtensions() as $name=>$extension) {

            $output->writeln(sprintf(
                '<comment>Installing extension %s from %s</comment>',
                $name,
                $extension->source
            ));

            $source = $extension->source;

            if (Git::isRepo($extension->source)) {
                $this->cloneFromGit($name, $extension);
                $source = $this->getExtensionFolder() . DIRECTORY_SEPARATOR . $name;
            }

            $this->deployExtension($name, $source);
            $output->writeln(sprintf(
                '<info>Installed extension %s</info>',
                $name
            ));
        }
    }

    /**
     * clone extension from Git repository
     * 
     * @param string $extension Extension name
     * @return void
     */
    protected function cloneFromGit($name, $extension)
    {
        $this->git = new Git($extension->source);
        $folder = $this->createExtensionfolder();

        $path = $folder . DIRECTORY_SEPARATOR . $name;
        $this->git->clonerepo($path);

        $this->output->writeln(sprintf(
            '<comment>Git checkout %s</comment>',
            $extension->branch
        ));

        $command = sprintf(
            'cd %s/.modman/%s && git pull origin %s 2>&1 && cd -',
            $this->config->getInstallPath(),
            $name,
            $extension->branch
        );
        exec($command, $result, $return);
        if (0 !== $return) {
            throw new Exception("Could not update extension $name");
        }
    }

    protected function deployExtension($name, $source)
    {
        $command = sprintf(
            'rsync -a -h --exclude="doc/*" --exclude="*.git" %s %s 2>&1',
            $source . DIRECTORY_SEPARATOR,
            $this->config->getInstallPath()
        );
        exec($command, $result, $return);

        if (0 !== $return) {
            throw new \Exception("Could not copy extension $name");
        }
    }

    protected function storePermissions()
    {
        file_put_contents($this->config->getInstallPath() . DIRECTORY_SEPARATOR . 'added_permissions.txt', serialize($this->config->getAddedPermissions()));
        file_put_contents($this->config->getInstallPath() . DIRECTORY_SEPARATOR . 'removed_permissions.txt', serialize($this->config->getRemovedPermissions()));

    }
    private function createExtensionfolder()
    {
        $folder = $this->getExtensionFolder();

        if (!is_dir($folder)) {
            $this->output->writeln(sprintf(
                '<comment>Creating extension folder %s</comment>',
                $folder
            ));
            mkdir($folder);
        }

        return $folder;
    }

    protected function getExtensionFolder()
    {
        return $this->config->getInstallPath() . DIRECTORY_SEPARATOR . '.modman';
    }
}

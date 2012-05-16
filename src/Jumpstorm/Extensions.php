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
        $this->config = new Config($input->getOption('config'), null, array('allowModifications' => true));

        file_put_contents($this->config->getInstallPath() . DIRECTORY_SEPARATOR . 'added_permissions.txt', serialize($this->config->getAddedPermissions()));
        file_put_contents($this->config->getInstallPath() . DIRECTORY_SEPARATOR . 'removed_permissions.txt', serialize($this->config->getRemovedPermissions()));

        foreach ($this->config->getExtensions() as $key=>$extension) {
            $checkout = 'master';

            if (!is_string($extension)) {
                $checkout  = $extension->branch;
                $extension = $extension->source;
            }

            $output->writeln(sprintf(
                '<comment>Installing extension %s from %s</comment>',
                $key,
                $extension
            ));

            $path = $extension;
            if (Git::isRepo($extension)) {
                $folder = $this->createExtensionfolder();

                $this->git = new Git($extension);

                $path = $folder . DIRECTORY_SEPARATOR . $key;
                $this->git->clonerepo($path);

                $output->writeln(sprintf(
                    '<comment>Git checkout %s</comment>',
                    $checkout
                ));

                $command = sprintf(
                     'cd %s/.modman/%s && git pull origin %s && cd -',
                     $this->config->getInstallPath(),
                     $key,
                     $checkout
                 );
                exec($command, $result, $return);
                if (0 !== $return) {
                    throw new Exception("Could not update extension $key");
                }
            }

            $command = sprintf('rsync -a -h --exclude="doc/*" --exclude="*.git" %s %s 2>&1', $path . DIRECTORY_SEPARATOR, $this->config->getInstallPath());
            exec($command, $result, $return);

            if (0 !== $return) {
                throw new Exception("Could not copy extension from $key");
            }
        }

        $scriptPath = implode(DIRECTORY_SEPARATOR, array(
            $this->config->getInstallPath(),
            'deployment',
            $this->config->getEnvironment(),
        ));
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

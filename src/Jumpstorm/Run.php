<?php
namespace Jumpstorm;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Setup Magento
 *
 * @package    Jumpstorm
 * @subpackage Jumpstorm
 * @author     Thomas Birke <thomas.birke@netresearch.de>
 */
class Run extends Base
{
    /**
     * @see vendor/symfony/src/Symfony/Component/Console/Command/Symfony\Component\Console\Command.Command::configure()
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('run');
        $this->setDescription('Combines the other commands: install Magento, prepare unittesting, install extensions and run plugins');
    }

    /**
     * @see vendor/symfony/src/Symfony/Component/Console/Command/Symfony\Component\Console\Command.Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->preExecute($input, $output);

        $commands = array(
            'magento',
            'unittesting',
            'extensions',
            'plugins'
        );

        foreach ($commands as $commandName)
        {
            $command = $this->getApplication()->find($commandName);

            $input->setArgument('command', $commandName);

            $returnCode = $command->run($input, $output);
        }
    }
}

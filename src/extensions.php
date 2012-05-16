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

class Extensions
{
    /* stdClass */
    private $config;

    public function __construct($configpath = null)
    {
        // Parse ini file
        $this->config = new Config($configpath, null, array('allowModifications' => true));
    }

    public function run()
    {
        file_put_contents($this->config->getInstallPath() . DIRECTORY_SEPARATOR . 'added_permissions.txt', serialize($this->config->getAddedPermissions()));
        file_put_contents($this->config->getInstallPath() . DIRECTORY_SEPARATOR . 'removed_permissions.txt', serialize($this->config->getRemovedPermissions()));

        try {
            foreach ($this->config->getExtensions() as $key=>$extension) {
                $checkout = 'master';

                if (!is_string($extension)) {
                    $checkout  = $extension->branch;
                    $extension = $extension->source;
                }

                Logger::notice("Installing extension $key from $extension");

                $path = $extension;
                if (Git::isRepo($extension)) {
                    $folder = $this->createExtensionfolder();

                    $this->git = new Git($extension);

                    $path = $folder . DIRECTORY_SEPARATOR . $key;
                    $this->git->clonerepo($path);

                    Logger::notice("Git checkout $checkout");
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

            Logger::notice('Checking for deployment scripts in path ' . $scriptPath);
        } catch (Exception $e) {
            Logger::error($e->getMessage());
        }
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

<?php
namespace Netresearch\Source;

/**
 * Git Handler for jumpstorm
 */
class Git
{
    private $repo;

    public function __construct($repo)
    {
        $this->repo = $repo;
    }

    /**
     * Clone git repo to desired location
     */
    public function clonerepo($path = '.')
    {
        $command = sprintf('git clone %s %s 2>&1', $this->repo, $path);
        exec($command, $result, $return);

        if (0 !== $return) {
            throw new \Exception(implode(PHP_EOL, $result));
        }
    }

    public function checkout($path, $branch)
    {
        $command = sprintf('cd %s; git checkout %s 2>&1; cd -', $path, $branch);
        exec($command, $result, $return);

        if (0 !== $return) {
            throw new \Exception(implode(PHP_EOL, $result));
        }
    }

    public function isRepo($path)
    {
        return (0 === strpos($path, 'git@') // path starts with "git://"
            || 0 === strpos($path, 'git://') // path starts with "git@"
            || 0 === strpos($path, 'http://') // path starts with "http://"
            || 0 === strpos($path, 'ssh://') // path starts with "ssh://"
            || is_dir($path . DIRECTORY_SEPARATOR . '.git') // dir contains .git folder
        );
    }
}

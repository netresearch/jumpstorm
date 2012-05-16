<?php
namespace Netresearch\Source;

/**
 * Git Handler for jumpstorm
 */
use Netresearch\Logger;

class Git extends \SourceBase implements \SourceInterface
{
    const GIT_DEFAULT_BRANCH = 'master';
    
    /**
     * @see SourceInterface::copy()
     */
    public function copy($source, $target, $branch = self::GIT_DEFAULT_BRANCH)
    {
        if (!\SourceBase::isGitRepo($repoUrl)) {
            throw new \Exception("Provided source is not a Git repository: $repoUrl");
        }
        
        $this->_cloneRepository($repoUrl, $targetPath);
        
        if ($branch != self::GIT_DEFAULT_BRANCH) {
            $this->_checkout($targetPath, $branch);
        }
    }

    /**
     * Clone git repo to desired location
     */
    protected function _cloneRepository($repoUrl, $targetPath)
    {
        Logger::log('Cloning Git repository');

        $command = sprintf('git clone %s %s 2>&1', $repoUrl, $targetPath);
        exec($command, $result, $return);

        if (0 !== $return) {
            throw new \Exception(implode(PHP_EOL, $result));
        }
    }

    protected function _checkout($targetPath, $branch)
    {
        Logger::log('Git checkout %s', array($branch));
        
        $command = sprintf('cd %s; git checkout %s 2>&1; cd -', $targetPath, $branch);
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

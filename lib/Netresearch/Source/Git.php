<?php
namespace Netresearch\Source;

use Netresearch\Logger;
use Netresearch\Source\SourceInterface;
use Netresearch\Source\Base as Source;

use \Exception as Exception;

/**
 * Git Handler for jumpstorm
 */
class Git extends Source implements SourceInterface
{
    const GIT_DEFAULT_BRANCH = 'master';
    
    /**
     * @see SourceInterface::copy()
     */
    public function copy($target, $branch = self::GIT_DEFAULT_BRANCH)
    {
        if (!Source::isGitRepo($this->source)) {
            throw new Exception('Provided source is not a Git repository: ' . $this->source);
        }

        if (Source::isGitRepo($target)) {
            $this->_pull($target);
        } else {
            $this->_cloneRepository($this->source, $target);
        }

        if ((null !== $branch)) {
            $this->_checkout($target, $branch);
        }
        $this->_submodules($target);
    }

    /**
     * Clone git repo to desired location
     */
    protected function _cloneRepository($repoUrl, $targetPath)
    {
        Logger::comment('Cloning Git repository');

        $command = sprintf('git clone %s --recursive %s 2>&1', $repoUrl, $targetPath);
        Logger::log($command);
        exec($command, $result, $return);

        if (0 !== $return) {
            throw new Exception(implode(PHP_EOL, $result));
        }
    }

    protected function _checkout($targetPath, $branch)
    {
        Logger::log('Git checkout %s', array($branch));
        
        $command = sprintf('cd %s; git checkout %s 2>&1; cd -', $targetPath, $branch);
        exec($command, $result, $return);

        if (0 !== $return) {
            throw new Exception(implode(PHP_EOL, $result));
        }
    }

    protected function _pull($targetPath)
    {
        Logger::log('Git pull %s', array($targetPath));

        $command = sprintf('cd %s; git pull 2>&1; cd -', $targetPath);
        exec($command, $result, $return);

        if (0 !== $return) {
            throw new Exception(implode(PHP_EOL, $result));
        }
    }

    protected function _submodules($targetPath)
    {
        Logger::log('Git submodule update --init --recursive %s', array($targetPath));

        $command = sprintf('cd %s; git submodule update --init --recursive 2>&1; cd -', $targetPath);
        exec($command, $result, $return);

        if (0 !== $return) {
            throw new Exception(implode(PHP_EOL, $result));
        }
    }
}

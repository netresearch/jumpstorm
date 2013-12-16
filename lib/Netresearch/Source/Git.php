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
     * Flag to indicate whether git clones should be performed recursively or not
     * @var boolean
     */
    protected $cloneRecursive = false;

    /**
     * @see SourceInterface::copy()
     */
    public function copy($target, $branch = self::GIT_DEFAULT_BRANCH)
    {
        if (!Source::isGitRepo($this->source)) {
            throw new Exception('Provided source is not a Git repository: ' . $this->source);
        }

        $this->_cloneRepository($this->source, $target);

        if ((null !== $branch) && (self::GIT_DEFAULT_BRANCH !== $branch)) {
            $this->_checkout($target, $branch);
        }
    }

    /**
     * Obtain the Git recursive flag if needed, emtpy string otherwise.
     *
     * @return string
     */
    protected function getRecursiveFlag()
    {
        return ($this->cloneRecursive ? '--recursive' : '');
    }

    /**
     * Instruct the source model to clone a repository recursively.
     *
     * @param boolean $cloneRecursive
     */
    public function setCloneRecursive($cloneRecursive)
    {
        $this->cloneRecursive = $cloneRecursive;
    }

    /**
     * Clone git repo to desired location
     */
    protected function _cloneRepository($repoUrl, $targetPath)
    {
        Logger::comment('Cloning Git repository');
        $command = sprintf('git clone %s %s %s 2>&1', $this->getRecursiveFlag(),  $repoUrl, $targetPath);
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
}

<?php
namespace Netresearch\Source;

use Netresearch\Logger;
use Netresearch\Source\SourceInterface;
use Netresearch\Source\Base as Source;

use \Exception as Exception;

/**
 * Mercurial Handler for jumpstorm
 */
class Hg extends Source implements SourceInterface
{
    const HG_DEFAULT_BRANCH = 'default';
    
    /**
     * @see SourceInterface::copy()
     */
    public function copy($target, $branch = self::HG_DEFAULT_BRANCH)
    {
        if (!Source::isHgRepo($this->source)) {
            throw new Exception('Provided source is not a Hg repository: ' . $this->source);
        }

        if (Source::isHgRepo($target)) {
            $this->_pull($target);
        } else {
            $this->_cloneRepository($this->source, $target);
        }

        if ((null !== $branch)) {
            $this->_update($target, $branch);
        }

    }

    /**
     * Clone hg repo to desired location
     */
    protected function _cloneRepository($repoUrl, $targetPath)
    {
        Logger::comment('Cloning Hg repository');

        $command = sprintf('hg clone %s %s 2>&1', $repoUrl, $targetPath);
        Logger::log($command);
        exec($command, $result, $return);

        if (0 !== $return) {
            throw new Exception(implode(PHP_EOL, $result));
        }
    }

    protected function _update($targetPath, $branch)
    {
        Logger::log('Hg update %s', array($branch));
        
        $command = sprintf('cd %s; hg update -C %s 2>&1; cd -', $targetPath, $branch);
        exec($command, $result, $return);

        if (0 !== $return) {
            throw new Exception(implode(PHP_EOL, $result));
        }
    }

    protected function _pull($targetPath)
    {
        Logger::log('Hg pull %s', array($targetPath));

        $command = sprintf('cd %s; hg pull -u 2>&1; cd -', $targetPath);
        exec($command, $result, $return);

        if (0 !== $return) {
            throw new Exception(implode(PHP_EOL, $result));
        }
    }

}

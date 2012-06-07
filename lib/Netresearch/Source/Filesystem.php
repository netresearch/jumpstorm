<?php
namespace Netresearch\Source;

use Netresearch\Logger;
use Netresearch\Source\SourceInterface;
use Netresearch\Source\Base as Source;

use \Exception as Exception;

class Filesystem extends Source implements SourceInterface
{
    public function copy($target, $branch = 'master')
    {
        if (!file_exists($this->source)) {
            throw new Exception("Source directory does not exist: '{$this->source}'");
        }
        
        // make sure that source ends with directory separator
        // as we want to copy its contents, not the directory itself
        $this->source = rtrim($this->source, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        Logger::notice('Copy files from %s', array($this->source));
        
        $command = sprintf('rsync -a -h %s %s 2>&1', $this->source, $target);
        Logger::log($command);
        exec($command, $result, $return);
        
        if (0 !== $return) {
            throw new Exception("Could not copy files to $target");
        }
    }
}

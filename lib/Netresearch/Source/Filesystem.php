<?php
namespace Netresearch\Source;

use Netresearch\Source\SourceInterface;
use Netresearch\Source\Base as Source;

class Filesystem extends Source implements SourceInterface
{
    public function copy($target, $branch = 'master')
    {
//         $command = sprintf('rsync -a -h --exclude="var/*" --exclude="*.git" %s %s 2>&1', $this->source, $target);
        $command = sprintf('rsync -a -h %s %s 2>&1', $this->source, $target);
        exec($command, $result, $return);
        
        if (0 !== $return) {
            throw new Exception("Could not copy files to $target");
        }
        
    }
}

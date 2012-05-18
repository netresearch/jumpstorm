<?php
namespace Netresearch\Source;

use Netresearch\Source\SourceInterface;
use Netresearch\Source\SourceBase as Source;

class Filesystem extends Source implements SourceInterface
{
    public function copy($target)
    {
        $command = sprintf('rsync -a -h --exclude="var/*" --exclude="*.git" %s %s 2>&1', $source, $target);
        exec($command, $result, $return);
    }
}

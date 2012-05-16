<?php
namespace Netresearch\Source;

class Filesystem extends \SourceBase implements \SourceInterface
{
    public function copy($source, $target)
    {
        $command = sprintf('rsync -a -h --exclude="var/*" --exclude="*.git" %s %s 2>&1', $source, $target);
        exec($command, $result, $return);
    }
}

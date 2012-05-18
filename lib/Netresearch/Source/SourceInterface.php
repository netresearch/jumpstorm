<?php
namespace Netresearch\Source;

interface SourceInterface
{
    public function copy($target, $branch = 'master');
}

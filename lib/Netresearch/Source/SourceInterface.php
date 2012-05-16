<?php


interface SourceInterface
{
    public function copy($source, $target, $branch = 'master');
}
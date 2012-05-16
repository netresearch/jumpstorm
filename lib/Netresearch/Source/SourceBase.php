<?php

use Netresearch\Source\Git;

abstract class SourceBase
{
    public static function isGitRepo($repoUrl)
    {
        return (
            (0 === strpos($repoUrl, 'git@')) // path starts with "git@"
            || (0 === strpos($repoUrl, 'git://')) // path starts with "git://"
            || (0 === strpos($repoUrl, 'http://')) // path starts with "http://"
            || (0 === strpos($repoUrl, 'ssh://')) // path starts with "ssh://"
            || (is_dir($repoUrl . DIRECTORY_SEPARATOR . '.git')) // dir contains .git folder
        );
    }
    
    public static function isFilesystemPath($sourcePath)
    {
        return (0 === strpos($repoUrl, '/')); // path is absolute filesystem path
    }
    
    public static function isHttpUrl($sourceUrl)
    {
        return (0 === strpos($repoUrl, 'http://')); // path is web path
    }
    
    /**
     * 
     * @param string $source
     */
    public static function getSourceModel($source)
    {
        if (self::isGitRepo($source)) {
            return new Git();
        } elseif (self::isFilesystemPath($source)) {
            return new Filesystem();
        } elseif (self::isHttpUrl($source)) {
            return new Http();
        }
    }
}

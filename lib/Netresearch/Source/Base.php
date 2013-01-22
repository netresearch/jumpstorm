<?php
namespace Netresearch\Source;

use Netresearch\Source\Git;
use \Exception as Exception;

abstract class Base
{
    protected $source;

    /**
     * set source to new instance
     *
     * @param string $source
     * @return Base
     */
    public function __construct($source)
    {
        $this->source = $source;
    }

    public static function isGitRepo($repoUrl)
    {
        return (
            (0 === strpos($repoUrl, 'git@')) // path starts with "git@"
            || (0 === strpos($repoUrl, 'git://')) // path starts with "git://"
            || (0 === strpos($repoUrl, 'http://')) // path starts with "http://"
            || (0 === strpos($repoUrl, 'ssh://')) // path starts with "ssh://"
            || self::isLocalGitDirectory($repoUrl)
            || self::isLocalGitDirectory($repoUrl. DIRECTORY_SEPARATOR . '.git')
        );
    }

    protected static function isLocalGitDirectory($path)
    {
        if (false == self::isFilesystemPath($path)) {
            return false;
        }
        $gitBareFolders = array(
            'HEAD',
            'branches',
            'config',
            'description',
            'hooks',
            'info',
            'objects',
            'refs'
        );
        foreach ($gitBareFolders as $gitBareFolder) {
            if (false == file_exists($path . DIRECTORY_SEPARATOR . $gitBareFolder)) {
                return false;
            }
        }
        return true;
    }
    
    public static function isFilesystemPath($sourcePath)
    {
        return (0 === strpos($sourcePath, '/')); // path is absolute filesystem path
    }
    
    public static function isHttpUrl($sourceUrl)
    {
        return (0 === strpos($sourceUrl, 'http://')); // path is web path
    }
    
    /**
     * 
     * @param string $source
     */
    public static function getSourceModel($source)
    {
        if (self::isGitRepo($source)) {
            return new Git($source);
        } elseif (self::isFilesystemPath($source)) {
            return new Filesystem($source);
        } elseif (self::isHttpUrl($source)) {
            return new Http($source);
        }
        
        throw new Exception("No applicable source model found for source '$source'");
    }
}

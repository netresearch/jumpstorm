<?php
namespace Netresearch\Source;

use Netresearch\Source\Git;
use \Exception as Exception;

abstract class Base
{
    protected $source;
    protected $baseTarget;

    /**
     * set source to new instance
     *
     * @param string $source
     * @return Base
     */
    public function __construct($source, $baseTarget=null)
    {
        $this->source     = $source;
        $this->baseTarget = $baseTarget;
    }

    public static function isGitRepo($repoUrl)
    {
        return (
            (0 === strpos($repoUrl, 'git@')) // path starts with "git@"
            || (0 === strpos($repoUrl, 'git://')) // path starts with "git://"
            || (0 === strpos($repoUrl, 'http://')) // path starts with "http://"
            || (0 === strpos($repoUrl, 'ssh://')) // path starts with "ssh://"
            || self::isLocalGitDirectory($repoUrl)
        );
    }

    public static function isMagentoConnectIdentifier($path)
    {
        return 0 === strpos($path, 'magentoconnect://');
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
     * get source model instance
     *
     * @param string $source     Source identifier
     * @param string $baseTarget Base target directory
     * @return Base
     */
    public static function getSourceModel($source, $baseTarget)
    {
        if (self::isGitRepo($source)) {
            return new Git($source);
        } elseif (self::isFilesystemPath($source)) {
            return new Filesystem($source);
        } elseif (self::isHttpUrl($source)) {
            return new Http($source);
        } elseif (self::isMagentoConnectIdentifier($source)) {
            return new MagentoConnect($source, $baseTarget);
        }
        
        throw new Exception("No applicable source model found for source '$source'");
    }
}

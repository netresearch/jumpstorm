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
     * @param string $source Location of the source files
     * @return Base
     */
    public function __construct($source)
    {
        $this->source     = $source;
    }

    /**
     * if source identifier seems to refer to a Git repository
     *
     * @param string $repoUrl Source identifier
     * @return boolean
     */
    public static function isGitRepo($repoUrl)
    {
        return (
            (0 === strpos($repoUrl, 'git@'))       // path starts with "git@"
            || (0 === strpos($repoUrl, 'git://'))  // path starts with "git://"
            || (0 === strpos($repoUrl, 'http://')) // path starts with "http://"
            || (0 === strpos($repoUrl, 'ssh://'))  // path starts with "ssh://"
            || self::isLocalGitDirectory($repoUrl)
        );
    }

    /**
     * if source identifier refers to Magento Connect
     *
     * @param string $path Source identifier
     * @return boolean
     */
    public static function isMagentoConnectIdentifier($path)
    {
        return 0 === strpos($path, 'magentoconnect://');
    }

    /**
     * if directory contains a Git folder (not a working copy, but its .git directory or a bare repository)
     *
     * @param string $path Path of a folder
     * @return boolean
     */
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

    /**
     * if source identifier points to a local file system directory
     *
     * @param string $sourcePath Source identifier
     * @return boolean
     */
    public static function isFilesystemPath($sourcePath)
    {
        $sourcePath = realpath($sourcePath);
        return file_exists($sourcePath); // path is absolute filesystem path
    }

    /**
     * if source identifier is a HTTP URI
     *
     * @param string $sourceUrl Source identifier
     * @return boolean
     */
    public static function isHttpUrl($sourceUrl)
    {
        return (0 === strpos($sourceUrl, 'http://')); // path is web path
    }

    /**
     * get source model instance
     *
     * @param string $source     Source identifier
     * @return Base
     */
    public static function getSourceModel($source)
    {
        if (false == is_string($source)) {
            $e = new \Exception('expected source to be a valid string, but got ' . gettype($source));
            echo $e->getTraceAsString();
            throw $e;
        }
        if (self::isGitRepo($source)) {
            return new Git($source);
        } elseif (self::isFilesystemPath($source)) {
            return new Filesystem($source);
        } elseif (self::isHttpUrl($source)) {
            return new Http($source);
        } elseif (self::isMagentoConnectIdentifier($source)) {
            return new MagentoConnect($source);
        }

        throw new Exception("No applicable source model found for source '$source'");
    }
}

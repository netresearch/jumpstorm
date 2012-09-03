<?php
namespace Netresearch;

use Symfony\Component\Console\Output\OutputInterface;
use \Exception as Exception;

/**
 * Simple logger
 */
class Logger
{
    const TYPE_COMMENT = 'comment';
    
    const TYPE_NOTICE = 'info';
    
    const TYPE_ERROR = 'error';

    const VERBOSITY_NONE   = 0;

    const VERBOSITY_MIN    = 1;

    const VERBOSITY_MEDIUM = 5;

    const VERBOSITY_MAX    = 10;

    protected static $verbosity = self::VERBOSITY_MEDIUM;
    
    protected static $output;
    
    public static function setOutputInterface(OutputInterface $output)
    {
        self::$output = $output;
    }

    public static function setVerbosity($verbosity)
    {
        self::$verbosity = $verbosity;
    }
    
    protected static function writeln($message, array $args = array(), $type = null)
    {
        if (self::VERBOSITY_NONE === self::$verbosity) {
            return;
        }
        if (self::VERBOSITY_MIN == self::$verbosity
            && self::TYPE_ERROR !== $type
        ) {
            return;
        }
        if (self::VERBOSITY_MEDIUM == self::$verbosity
            && self::TYPE_ERROR !== $type
            && self::TYPE_NOTICE !== $type
        ) {
            return;
        }

        if (!self::$output) {
            throw new Exception('No output interface given');
        }

        self::$output->writeln(
            is_null($type)
            ? vsprintf("$message", $args)
            : vsprintf("<$type>$message</$type>", $args)
        );
    }
    
    public static function log($message, array $args = array(), $type=null)
    {
        self::writeln($message, $args, $type);
    }
    
    public static function comment($message, array $args = array())
    {
        self::writeln($message, $args, self::TYPE_COMMENT);
    }

    public static function notice($message, array $args = array())
    {
        self::writeln($message, $args, self::TYPE_NOTICE);
    }

    public static function error($message, array $args = array(), $stopExecution = true)
    {
        self::writeln($message, $args, self::TYPE_ERROR);
        if ($stopExecution) {
            exit;
        }
    }

    public static function success($message, array $args = array())
    {
        self::notice($message, $args);
    }

    public static function warning($message, array $args = array())
    {
        self::comment($message, $args);
    }
}

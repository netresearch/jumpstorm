<?php
namespace Netresearch;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Simple logger
 */
class Logger
{
    const TYPE_COMMENT = 'comment';
    
    const TYPE_NOTICE = 'info';
    
    const TYPE_ERROR = 'error';
    
    protected static $output;
    
    public static function setOutputInterface(OutputInterface $output)
    {
        self::$output = $output;
    }
    
    protected static function writeln($message, array $args = array(), $type = null)
    {
        if (!self::$output) {
            throw new Exception('No output interface given');
        }
        self::$output->writeln(
            is_null($type)
            ? vsprintf("$message", $args)
            : vsprintf("<$type>$message</$type>", $args)
        );
    }
    
    public static function log($message, array $args = array())
    {
        self::writeln($message, $args);
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
}

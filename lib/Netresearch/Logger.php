<?php
namespace Netresearch;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Simple logger
 */
class Logger
{
    // log => comment
    // question
    // info => notice
    // error => error
    
    const TYPE_LOG = 'comment';
    
    const TYPE_NOTICE = 'info';
    
    const TYPE_ERROR = 'error';
    
    protected static $output;
    
    public static function setOutputInterface(OutputInterface $output)
    {
        self::$output = $output;
    }
    
    public static function writeln($message, array $args = array(), $type = self::TYPE_LOG)
    {
        if (!self::$output) {
            throw new Exception('No output interface given');
        }
        self::$output->writeln(sprintf("<$type>$message</$type>", $args));
    }
    
    public static function log($message, array $args = array())
    {
        self::writeln($message, $args, self::TYPE_LOG);
    }

    public static function notice($message, array $args = array())
    {
        self::writeln($message, $args, self::TYPE_NOTICE);
    }

    public static function error($message, $message, array $args = array(), $stopExecution = true)
    {
        self::writeln($message, $args, self::TYPE_ERROR);
        if ($stopExecution) {
            exit;
        }
    }
}

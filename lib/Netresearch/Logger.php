<?php
/**
 * Simple logger
 */
class Logger
{
    public static function log($message)
    {
        echo sprintf("\033[32m==>\033[37m %s %s", $message, PHP_EOL); 
    }

    public static function notice($message)
    {
        self::log(sprintf("\033[33mNotice\033[37m: %s", $message));
    }

    public static function error($message, $stopExecution = true)
    {
        self::log(sprintf("\033[31mError\033[37m: %s", $message));

        if ($stopExecution) exit;
    }
}

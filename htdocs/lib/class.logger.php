<?php

class Logger {
    const CRITICAL = 4;
    const ERROR = 3;
    const WARNING = 2;
    const INFO = 1;
    const DEBUG = 0;

    private static $file,$level;

    public static function configure(array $config) {
        self::$file = $config['file'];
        switch ($config['level']) {
            case 'critical':
                self::$level = self::CRITICAL;
            case 'error':
                self::$level = self::ERROR;
            case 'warning':
                self::$level = self::WARNING;
            case 'info':
                self::$level = self::INFO;
            case 'debug':
                self::$level = self::DEBUG;
            default:
                self::$level = self::DEBUG;
        }
    }

    private static function write_log($txt, $var) {
        $date = date("Y-m-d H:i:s");   
        $log = '['.$date.'] '.$txt.PHP_EOL;
 
        file_put_contents(self::$file, $log, FILE_APPEND | LOCK_EX);
        if (self::$level <= self::DEBUG && !empty($var)) {
            file_put_contents(self::$file, var_dump($var).PHP_EOL , FILE_APPEND | LOCK_EX);
        }
    }

    public static function critical($txt, $var=NULL) {
        if (self::$level <= self::CRITICAL ) {
            self::write_log($txt, $var);
        } 
    }

    public static function error($txt, $var=NULL) {
        if (self::$level <= self::ERROR ) {
            self::write_log($txt, $var);
        } 
    }

    public static function warning($txt, $var=NULL) {
        if (self::$level <= self::WARNING) {
            self::write_log($txt, $var);
        } 
    }

    public static function info($txt, $var=NULL) {
        if (self::$level <= self::INFO) {
            self::write_log($txt, $var);
        } 
    }
}

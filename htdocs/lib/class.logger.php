<?php

class Logger {
    const LEVEL = array('DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL');
    const DEBUG = 0;
    const INFO = 1;
    const WARNING = 2;
    const ERROR = 3;
    const CRITICAL = 4;

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

    private static function write_log($txt, $level, $user, $var) {
        $date = date("Y-m-d H:i:s");
        if (empty($user)) {
            $log = '['.$date.'] '.self::LEVEL[$level].': '.$txt.' ['.$_SERVER['REMOTE_ADDR'].']'.PHP_EOL;
        } else {
            $log = '['.$date.'] '.self::LEVEL[$level].': '.$txt.' [by '.$user.']'.PHP_EOL;
        }
 
        file_put_contents(self::$file, $log, FILE_APPEND | LOCK_EX);
        if (self::$level <= self::DEBUG && !empty($var)) {
            file_put_contents(self::$file, var_dump($var).PHP_EOL , FILE_APPEND | LOCK_EX);
        }
    }

    public static function critical($txt, $user=NULL, $var=NULL) {
        if (self::$level <= self::CRITICAL ) {
            self::write_log($txt, self::CRITICAL, $user, $var);
        } 
    }

    public static function error($txt, $user=NULL, $var=NULL) {
        if (self::$level <= self::ERROR ) {
            self::write_log($txt, self::ERROR, $user, $var);
        } 
    }

    public static function warning($txt, $user=NULL, $var=NULL) {
        if (self::$level <= self::WARNING) {
            self::write_log($txt, self::WARNING, $user, $var);
        } 
    }

    public static function info($txt, $user=NULL, $var=NULL) {
        if (self::$level <= self::INFO) {
            self::write_log($txt, self::INFO, $user, $var);
        } 
    }
}

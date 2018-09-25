<?php

class Logger {
    const LEVEL = array('DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL');
    const DEBUG = 0;
    const INFO = 1;
    const WARNING = 2;
    const ERROR = 3;
    const CRITICAL = 4;

    private static $level;

    public static function configure($loglevel) {
        switch ($loglevel) {
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
	$facility = empty($user) ? LOG_AUTH : LOG_LOCAL0;
	$id = empty($user) ? $_SERVER['REMOTE_ADDR'] : $user;
	openlog("evoadmin-mail", LOG_PID | LOG_PERROR, $facility);
	syslog($level, "$txt [$id]");
	closelog();
    }

    public static function critical($txt, $user=NULL, $var=NULL) {
        if (self::$level <= self::CRITICAL ) {
            self::write_log($txt, LOG_CRIT, $user, $var);
        } 
    }

    public static function error($txt, $user=NULL, $var=NULL) {
        if (self::$level <= self::ERROR ) {
            self::write_log($txt, LOG_ERR, $user, $var);
        } 
    }

    public static function warning($txt, $user=NULL, $var=NULL) {
        if (self::$level <= self::WARNING) {
            self::write_log($txt, LOG_WARNING, $user, $var);
        } 
    }

    public static function info($txt, $user=NULL, $var=NULL) {
        if (self::$level <= self::INFO) {
            self::write_log($txt, LOG_INFO, $user, $var);
        } 
    }
}

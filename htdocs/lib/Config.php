<?php

class Config {
    private static $name='Evoadmin Mail', $mail='root@localhost', $log_level='error', $ldap=array();
    public static function load($file) {
        $ini = parse_ini_file($file, true);
        self::$name = $ini['global']['name'];
        self::$mail = $ini['global']['mail'];
        self::$log_level = $ini['global']['log_level'];
        self::$ldap = $ini['ldap'];
    }

    public static function getName() {
        return self::$name;
    }

    public static function getMail() {
        return self::$mail;
    }

    public static function getLogLevel() {
        return self::$log_level;
    }

    public static function getLdapUri() {
        return 'ldap://'.self::$ldap['host'].':'.self::$ldap['port'];
    }

    public static function getLdapDN() {
        return self::$ldap['admin_dn'];
    }

    public static function getLdapPass() {
        return self::$ldap['admin_pass'];
    }

    public static function getLdapBase() {
        return self::$ldap['base'];
    }

    public static function getSuperadmin() {
        return self::$ldap['superadmin'];
    }
}

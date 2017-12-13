<?php

const CONFIG_FILE = './config/conf.php';
const CONNECT_FILE = './config/connect.php';

spl_autoload_register(function ($class) {
    if (file_exists("vendor/evolibs/$class.php")) {
        include_once("vendor/evolibs/$class.php");
    } else {
        $class = strtolower($class);
        include_once("lib/class.$class.php");
    }
});

if (file_exists(CONFIG_FILE)) {
    require_once(CONFIG_FILE);
    global $conf;
    if ($conf['domaines']['driver'] == 'ldap') {
        if (file_exists(CONNECT_FILE)) {
            require_once(CONNECT_FILE);
        } else {
#            EvoLog::log('You must create '.CONNECT_FILE);
            return false;
        }
    }
} else {
#    EvoLog::log('You must create '.CONFIG_FILE);
    return false;
}

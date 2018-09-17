<?php

spl_autoload_register(function ($class) {
    $class = strtolower($class);
    if (file_exists("lib/class.$class.php")) {
        require_once("lib/class.$class.php");
    }
});

require_once 'Twig/autoload.php';

$config = parse_ini_file('../config/config.ini', true);

Logger::configure($config['global']['log_level']);
MailNotify::configure($config['global']);

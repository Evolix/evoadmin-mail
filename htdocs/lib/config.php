<?php

spl_autoload_register(function ($class) {
    $class = strtolower($class);
    include_once("lib/class.$class.php");
});

$config = parse_ini_file('../config/config.ini', true);

Logger::configure($config['log']);

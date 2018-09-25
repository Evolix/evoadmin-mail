<?php

session_name('EVOADMIN_SESS');
session_start();

spl_autoload_register(function ($class) {
    if (file_exists("lib/$class.php")) {
        require_once("lib/$class.php");
    }
    $class = strtolower($class);
    if (file_exists("lib/class.$class.php")) {
        require_once("lib/class.$class.php");
    } 
});

DefaultController::init();
FormController::init();
PageController::init();

?>

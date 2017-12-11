<?php

spl_autoload_register(function ($class) {
    if (file_exists("vendor/evolibs/$class.php")) {
        include_once("vendor/evolibs/$class.php");
    } else {
        $class = strtolower($class);
        include_once("lib/class.$class.php");
    }
});

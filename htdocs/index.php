<?php

spl_autoload_register(function ($class) {
    if (file_exists("lib/$class.php")) { require_once("lib/$class.php"); }
});

DefaultController::init();
FormController::init();
PageController::init();

?>

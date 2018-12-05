<?php

spl_autoload_register(function ($class) {
    if (file_exists("lib/$class.php")) { require_once("lib/$class.php"); }
});

Config::load();
Logger::init();
MailNotify::init();

session_name('EVOADMIN_SESS');
session_start();

// Get content from LDAP
$server = NULL;
if (!empty($_SESSION['login'])) {
    try {
        $server = new LdapServer($_SESSION['login']);
    } catch (Exception $e) {
        PageController::$alerts[] = array('type' => 2, 'message' => $e->getMessage());
    }
    FormController::init($server);
} else {
    if (!empty($_POST['login'])) {
        try {
            $input = filter_input_array(INPUT_POST, array(
                'login' => array('filter' => FILTER_SANITIZE_STRING, 'flags' => FILTER_FLAG_STRIP_HIGH)
                ,'password' => array('filter' => FILTER_SANITIZE_STRING, 'flags' => FILTER_FLAG_STRIP_HIGH)
            ));
            $server = new LdapServer($input['login']);
            $server->login($input['password']);
            $_SESSION['login'] = $server->getLogin();
        } catch (Exception $e) {
            PageController::$alerts[] = array('type' => 2, 'message' => $e->getMessage());
            $server = NULL;
        }
    }
}

PageController::init($server);

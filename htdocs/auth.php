<?php

// Load config and autoload class
require_once("lib/config.php");

session_name('EVOADMIN_SESS');
session_start();

ob_start();

$logout = isset($_SESSION['login']) ? true : false;

if ($logout) {
    session_unset('EVOADMIN_SESS');
    session_destroy();
}

if (!empty($_POST['login'])) {
    try {
        $login = htmlentities(strip_tags($_POST['login']),ENT_NOQUOTES);
        $password = htmlentities(strip_tags($_POST['password']),ENT_NOQUOTES);
        $server = new LdapServer($login, $config['ldap']);
        $server->login($password);
        $_SESSION['login'] = $server->getLogin();
        header("location: superadmin.php\n\n");
        exit(0);
    } catch (Exception $e) {
        $alerts[] = array('type' => 2, 'message' => $e->getMessage());
    }
}

print $twig->render('login.html', array(
    'page_name' => $config['global']['name'].' - Login'
    ,'alerts' => $alerts
    ,'logout' => $logout
));

ob_end_flush();

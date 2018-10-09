<?php

class DefaultController {
    protected static $alerts=array(),$server;
    public static function init() {
        Config::load('../config/config.ini');

        Logger::init();
        MailNotify::init();

        session_name('EVOADMIN_SESS');
        session_start();

        // Get content from LDAP
        if (!empty($_SESSION['login'])) {   
            try {
                self::$server = new LdapServer($_SESSION['login']);
            } catch (Exception $e) {
                self::$alerts[] = array('type' => 2, 'message' => $e->getMessage());
            }
            FormController::init();
        } else {
            if (!empty($_POST['login'])) {
                try {
                    $input = filter_input_array(INPUT_POST, array(
                        'login' => array('filter' => FILTER_SANITIZE_STRING, 'flags' => FILTER_FLAG_STRIP_HIGH)
                        ,'password' => array('filter' => FILTER_SANITIZE_STRING, 'flags' => FILTER_FLAG_STRIP_HIGH)
                    ));
                    self::$server = new LdapServer($input['login']);
                    self::$server->login($input['password']);
                    $_SESSION['login'] = self::$server->getLogin();
                } catch (Exception $e) {
                    self::$alerts[] = array('type' => 2, 'message' => $e->getMessage());
                }
            }
        }
        PageController::init();
    }
}

<?php

class DefaultController {
    protected static $logged=false, $config=array(), $alerts=array(),$server;
    public static function init() {
        self::$config = parse_ini_file('../config/config.ini', true);
        
        Logger::configure(self::$config['global']['log_level']);
        MailNotify::configure(self::$config['global']);

        session_name('EVOADMIN_SESS');
        session_start();

        // Get content from LDAP
        if (!empty($_SESSION['login'])) {   
            self::$logged = true;
            try {
                self::$server = new LdapServer($_SESSION['login'], self::$config['ldap']);
            } catch (Exception $e) {
                self::$alerts[] = array('type' => 2, 'message' => $e->getMessage());
            }
        } else {
            if (!empty($_POST['login'])) {
                try {
                    $input = filter_input_array(INPUT_POST, array(
                        'login' => array('filter' => FILTER_SANITIZE_STRING, 'flags' => FILTER_FLAG_STRIP_HIGH)
                        ,'password' => array('filter' => FILTER_SANITIZE_STRING, 'flags' => FILTER_FLAG_STRIP_HIGH)
                    ));
                    self::$server = new LdapServer($input['login'], self::$config['ldap']);
                    self::$server->login($input['password']);
                    self::$logged = true;
                    $_SESSION['login'] = self::$server->getLogin();
                } catch (Exception $e) {
                    self::$alerts[] = array('type' => 2, 'message' => $e->getMessage());
                }
            }
        }
    }

    protected static function needSuperAdmin() {
        if (!self::$server->isSuperAdmin()) {
            self::$alerts[] = array('type' => 2, 'message' => "Super Adminsitrateur seulement !");
            return false;
        } else { return true; }
    }
}

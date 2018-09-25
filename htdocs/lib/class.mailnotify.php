<?php

require_once 'Twig/autoload.php';

class MailNotify {
    private static $twig, $adminmail;

    public static function configure($config) {
        $loader = new Twig_Loader_Filesystem('tpl/mail');
        self::$twig = new Twig_Environment($loader, array(
            'cache' => false
        ));

        self::$adminmail = !empty($config['mail']) ? $config['mail'] : 'root@localhost';
    }

    public static function addDomain($domain) {
        $headers = "From: ".self::$adminmail;

        # Notification mail to admin mail
        $mail_notif = self::$twig->render('domain/add_notif.txt.twig', array('domain' => $domain));
        mail(self::$adminmail, 'Création du domaine '.$domain, $mail_notif, $headers);
    }

    public static function addAccount($domain, $mail, $name, $password) {
        $headers = "From: ".self::$adminmail;

        # Welcome mail for account initialization
        $mail_init = self::$twig->render('account/init.txt.twig', array('mail' => $mail, 'name' => $name));
        mail($mail, 'Bienvenue !', $mail_init, $headers);

        # Notification mail to admin mail
        $mail_notif = self::$twig->render('account/add_notif.txt.twig', array('mail' => $mail, 'name' => $name, 'password' => $password));
        mail(self::$adminmail, 'Création du compte '.$mail, $mail_notif, $headers);
    }
}

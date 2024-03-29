<?php

require_once '/usr/share/php/Twig/autoload.php';

class MailNotify {
    private static $twig, $adminmail;

    public static function init() {
        $loader = new Twig\Loader\FilesystemLoader('tpl/mail');
        self::$twig = new Twig\Environment($loader, array(
            'cache' => false
        ));

        self::$adminmail = Config::getMail();
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

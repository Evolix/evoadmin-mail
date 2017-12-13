<?php

session_name('EVOADMIN_SESS');
session_start();

if (empty($_SESSION['login'])) {
    header("location: auth.php\n\n");
    exit(0);
} else {
    try {
        $server = new LdapServer($_SESSION['login']);
        if (!empty($_GET['domain'])) {
            try {
                $domain = new LdapDomain($server, Html::clean($_GET['domain']));
            } catch (Exception $e_d) {
                print '<div class="alert alert-danger" role="alert">'.$e_d->getMessage();
                exit(1);
            }
        }
    } catch (Exception $e_s) {
        print '<div class="alert alert-danger" role="alert">'.$e_s->getMessage().'</div>';
        exit(1);
    }
}

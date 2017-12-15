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
            $domain = new LdapDomain($server, Html::clean($_GET['domain']));
            if (!empty($_GET['account'])) {
                $account = new LdapAccount($domain, Html::clean($_GET['account']));
            }
        }
    } catch (Exception $e) {
        print '<div class="alert alert-danger" role="alert">'.$e->getMessage().'</div>';
        exit(1);
    }
}

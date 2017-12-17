<?php

session_name('EVOADMIN_SESS');
session_start();

if (empty($_SESSION['login'])) {
    header("location: auth.php\n\n");
    exit(0);
} else {
    try {
        $server = new LdapServer($_SESSION['login'], LDAP_BASE, LDAP_ADMIN_DN, LDAP_ADMIN_PASS, LDAP_URI);
        if (!empty($_GET['domain'])) {
            $domain = htmlentities(strip_tags($_GET['domain']),ENT_NOQUOTES);
            $domain = new LdapDomain($server, $domain);
            if (!empty($_GET['account'])) {
                $account = htmlentities(strip_tags($_GET['account']),ENT_NOQUOTES);
                $account = new LdapAccount($domain, $account);
            }
            if (!empty($_GET['alias'])) {
                $alias = htmlentities(strip_tags($_GET['alias']),ENT_NOQUOTES);
                $alias = new LdapAlias($domain, $alias);
            }
        }
    } catch (Exception $e) {
        print '<div class="alert alert-danger" role="alert">'.$e->getMessage().'</div>';
        exit(1);
    }
}

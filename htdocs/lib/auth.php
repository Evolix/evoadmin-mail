<?php

session_name('EVOADMIN_SESS');
session_start();

if (empty($_SESSION['login'])) {
    header("location: auth.php\n\n");
    exit(0);
} else {
    if ($server = new LdapServer($_SESSION['login'])) {
        $domains = $server->getDomains();
    } else {
        print "<div class=\"alert alert-danger\" role=\"alert\">Erreur de connexion LDAP !</div>";
        exit(1);
    }
}

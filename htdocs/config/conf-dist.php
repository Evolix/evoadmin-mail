<?php

// Email pour les notifications
$conf['admin']['mail'] = 'admin@example.com';
// login des superadmins
// Note: utile uniquement si domaines/driver=ldap, laisser vide sinon...
$conf['admin']['logins'] = array('foo');
// What do you want?
// 0 = nothing...
// 1 = only mail accounts
// 2 = only samba accounts
// 3 = mail and samba accounts
$conf['admin']['what'] = 3;
// use hook.php instead of hook-dist.php
$conf['admin']['use_hook'] = false;
// enable quota
$conf['admin']['quota'] = true;

// compatibilite LDAP
$conf['evoadmin']['version'] = 3;
$conf['url']['webroot'] = '/evoadmin';

$conf['domaines']['onlyone'] = true;
$conf['domaines']['driver'] = 'file';
$conf['domaines']['file']['all'] = array('example.com');
$conf['domaines']['file']['gid'] = 1000;
// Pack Mail "virtuel"... attention
// uniquement possible si $conf['admin']['what']=1 !!
//$conf['domaines']['ldap']['virtual'] = false;

// Mode cluster
// Uniquement en mode mail seul et des utilisateurs virtuels
$conf['evoadmin']['cluster'] = true;

// auth SMTP by default ?
$conf['evoadmin']['useauthsmtp'] = false;

// Si comptes virtuels
$conf['unix']['uid'] = 2022;

// Si pas virtuel
$conf['unix']['minuid'] = 1000;
$conf['unix']['mingid'] = 1000;

$conf['html']['title'] = "Interface d'administration XXX";

// gestion des logs
$conf['log']['priority'] = PEAR_LOG_DEBUG;
$conf['log']['name'] = '/var/log/evoXXX.log';
$conf['log']['software'] = 'evoXXX';
$conf['log']['enabled'] = true;

// samba
$conf['samba']['dn'] = 'DOMAINNAME';
$conf['samba']['sid'] = 'S-1-5-21-XXX-XXX-XXX';
$conf['samba']['admin_default'] = false;

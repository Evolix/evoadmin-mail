<?php

/**
 * Secrete parameters
 *
 * $Id: connect-dist.php,v 1.3 2007-05-22 21:12:23 reg Exp $
 *
 * @author Gregory Colpart <reg@evolix.fr>
 * @version 1.0
 */

#define("LDAP_URI","ldap://127.0.0.1");
$ldap_servers = array('ldap://127.0.0.1', 'ldap://12');
define("LDAP_BASE","dc=example,dc=com");
define("LDAP_ADMIN_DN","cn=admin,dc=example,dc=com");
define("LDAP_ADMIN_PASS","xxxxx");

define("SUDOBIN","/usr/bin/sudo");
define("SUDOSCRIPT","/usr/share/scripts/evoadmin.sh");
define("SUDOPASS","xxxxxx");

define ('SERVEUR', "localhost");

define('SERVEUR','localhost');
define('SERVEURPORT',3306);
define('BASE','horde');
define('NOM', 'horde');
define('PASSE', 'xxxx');

?>

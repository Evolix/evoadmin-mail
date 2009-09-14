<?php

function display($msg)
{
    echo "<p class='display'>" . $msg . "</p>\n";
}

// teste si l'utilisateur est superadmin
function superadmin($login) {

    global $conf;
    
    foreach ($conf['admin']['logins'] as $admin) {
        if ( Html::clean($login) == $admin ) {
            return TRUE;
        }
    }
    return FALSE;
}


// execution du script shell associe
function evoexec($cmd) {
    //exec(SUBIN . " " . SUUSER . " -c " . SUDOBIN . " '$cmd'");
    return exec(SUDOBIN . " " . SUDOSCRIPT . " -p " . SUDOPASS . " $cmd");
}


// retourne le quota d'un utilisateur ou d'un groupe
function getquota($who,$what) {

global $conf;

    if ( $what == 'user') {
        $quota = evoexec("-qu $who");
    } elseif ( $what == 'group') {
        if ( $conf['domaines']['driver'] == 'file' ) {
            $quota = evoexec("-s");
        } elseif ( $conf['domaines']['driver'] == 'ldap' ) {
            $quota = evoexec("-qg $who");
	}
    }

    list ($now,$limit) = split("/",$quota);
    $now = $now / 1024;
    $limit = $limit / 1024;
    $quota = "<b>" . Math::arrondi($now). "M</b>/" .Math::arrondi($limit). "M";

    return $quota;
}

// commande shell a lancer pour creer un utilisateur
function unix_add($user,$group=NULL) {

    if ( $group == NULL) {
        $group = getgid();
    }
    evoexec("-a -u $user -g $group");
}

// commande shell a lancer pour creer un domaine
function domain_add($group) {
    evoexec("-a -v -g $group");
}

// commande shell a lancer pour supprimer un utilisateur
function unix_del($user) {

    evoexec("-d -u $user");
}

// renvoie le gidNumber associe a un domaine
function getgid($domain=NULL) {

    global $conf;

    if ( $conf['domaines']['driver'] == 'file' ) {
        return $conf['domaines']['file']['gid'];
    } elseif ( $conf['domaines']['driver'] == 'ldap' ) {

        $ldapconn = Ldap::lda_connect(LDAP_ADMIN_DN,LDAP_ADMIN_PASS);
        $filter="(&(cn=" .$domain. ")(gidnumber=*))";
        $sr=ldap_search($ldapconn, LDAP_BASE, $filter);
        $info = ldap_get_entries($ldapconn, $sr);
        ldap_unbind($ldapconn);

        if ($info['count']) {
            return (int) $info[0]["gidnumber"][0];
        } else {
            return -1;
        }

    } else {
        return -1;
    }
}

// renvoie le 1er uidNumber disponible
function getfreeuid() {

    global $conf;

    $ldapconn = Ldap::lda_connect(LDAP_ADMIN_DN,LDAP_ADMIN_PASS);
    $filter="(uidNumber=*)";
    $sr=ldap_search($ldapconn, LDAP_BASE, $filter);
    $info = ldap_get_entries($ldapconn, $sr);
    ldap_unbind($ldapconn);

    $uids = array();

    foreach ($info as $entry) {
        array_push($uids,$entry['uidnumber'][0]);
    }

    sort($uids);
    $uid = max(array_pop($uids)+1,$conf['unix']['minuid']);

    return (int) $uid;
}

// renvoie le 1er uidNumber disponible
function getfreegid() {

    global $conf;

    $ldapconn = Ldap::lda_connect(LDAP_ADMIN_DN,LDAP_ADMIN_PASS);
    $filter="(gidNumber=*)";
    $sr=ldap_search($ldapconn, LDAP_BASE, $filter);
    $info = ldap_get_entries($ldapconn, $sr);
    ldap_unbind($ldapconn);

    $gids = array();

    foreach ($info as $entry) {
        array_push($gids,$entry['gidnumber'][0]);
    }

    sort($gids);
    $gid = max(array_pop($gids)+1,$conf['unix']['mingid']);

    return (int) $gid;
}


// get number of account or aliases for a domain
function getnumber($domain,$type) {

    global $conf;

    $ldapconn = Ldap::lda_connect(LDAP_ADMIN_DN,LDAP_ADMIN_PASS);
    if ( $type == 'compte' ) {
        $filter="(objectClass=posixAccount)";

    } elseif ( $type == 'mail' ) {
        $filter="(objectClass=mailAccount)";

    } elseif ( $type == 'alias' ) {
        $filter="(objectClass=mailAlias)";

        // compatibilite anciens schemas
        if ($conf['evoadmin']['version'] == 1) {
		    $filter="(&(objectClass=mailAlias)(onlyAlias=TRUE))";
        } 

    }  elseif ( $type == 'smb' ) {
        $filter="(objectClass=sambaSamAccount)";
    } 

    if (! $conf['domaines']['onlyone'])  {

        // compatibilite anciens schemas
        if ($conf['evoadmin']['version'] <= 2) {
            $rdn= "domain=" .$domain. "," .LDAP_BASE;
        } else {
            $rdn= "cn=" .$domain. "," .LDAP_BASE;
        }

    } else {
        //$rdn= "ou=people," .LDAP_BASE;
        $rdn= LDAP_BASE;
    }

    $sr=ldap_search($ldapconn, $rdn, $filter);
    $info = ldap_get_entries($ldapconn, $sr);
    ldap_unbind($ldapconn);

        return $info['count'];
}

function getsambagroups($type) {

        global $conf;

	// Si la liste des groupes est defini dans la config on l'utilise

	if($type == "unix" && isset($conf['samba']['unixgroups'])) {
		return $conf['samba']['unixgroups'];
	}

	if($type == "smb" && isset($conf['samba']['smbgroups'])) {
		return $conf['samba']['smbgroups'];
	}

	// sinon on interroge LDAP

        $ldapconn = Ldap::lda_connect(LDAP_ADMIN_DN,LDAP_ADMIN_PASS);
	$filter = "(objectClass=sambaGroupMapping)";
	$rdn = LDAP_BASE;
        $sr=ldap_search($ldapconn, $rdn, $filter);
        $info = ldap_get_entries($ldapconn, $sr);
        ldap_unbind($ldapconn);

	$ret = array();
	for($i=0; $i<$info['count']; $i++) {

		$entry = $info[$i];
		$cn = $entry['cn'][0];

		if($type == "unix") {
			$ret[$cn] = $entry['gidnumber'][0];
		} elseif($type == "smb") {
			$tmp = explode('-', $entry['sambasid'][0]);
			$ret[$cn] = "-".array_pop($tmp);
		}
	}

	return $ret;
}

/**
 * Verifie qu'un login est incorrect
 * entre 2 et 30 caracteres
 * en lettres minuscule, chiffres, '-', '.' ou '_'
 * pour le premier et dernier caracteres : seuls lettres et minuscules
 * et chiffres sont possibles
 */
function badname($login)
{
    return (!preg_match('`^([a-z0-9][a-z0-9\-\.\_]{0,28}[a-z0-9])$`',$login));
}

/**
 * Ajouter la composante @domaine
 */
function adddomain(&$item,$key)
{
    if (preg_match('`@`',$item)) {
        print "<p class='error'>Ne pas inclure de @ dans les mails acceptes&nbsp;!</p>";
        exit(1);
    }

    if (!empty($item)) {
        $item = "$item". "@".$_SESSION['domain'];
    }
}



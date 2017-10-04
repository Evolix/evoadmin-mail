<?php

/**
 * Bibliotheques LDAP (PHP4 et PHP5)
 *
 * Copyright (c) 2004-2008 Evolix - Tous droits reserves 
 * $Id: Ldap.php,v 1.2 2008-09-29 11:04:52 tmartin Exp $
 *
 * Fonctions utiles pour utilisation PHP et OpenLDAP
 * 
 */


class Ldap {

    /**
     * Connexion a une base OpenLDAP
     * les constantes LDAP_URI devront etre definies
     * il convient de les definir dans un fichier connect.php
     */

    function lda_connect($binddn="none",$pass="") {

        $ldapconn = @ldap_connect(LDAP_URI)
            or die( "Impossible de se connecter au serveur LDAP {$ldaphost}" );
        if (!ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3)) {
            echo 'Impossible de modifier la version du protocole à 3';
        }

        if ($binddn != "none") {
            if (!ldap_bind($ldapconn, $binddn, $pass)) {
                return FALSE;
            }
        }

        return $ldapconn;

    }

    /**
     * suppression d'entrees OpenLDAP
     * recursivite possible
     */

    function lda_del($ldapconn, $dn , $recursive=FALSE) { 
        if($recursive == FALSE) { 
            return(ldap_delete($ldapconn, $dn));
        } else {
            $sr=ldap_list($ldapconn, $dn, "ObjectClass=*");
            $info = ldap_get_entries($ldapconn, $sr);

            for($i=0;$i<$info['count'];$i++) {
                $result= lda_del($ldapconn, $info[$i]['dn'],$recursive);
                if(!$result) {
                    return($result);
                }
            }
            return(ldap_delete($ldapconn, $dn));
        }
    }

    /**
     * getfreegid()
     * obtenir le plus petit GID disponible
     */

    function getfreegid() {
        $gid = exec("sudo /usr/share/scripts/script.sh -g");
        return $gid;
    }

    /**
     * getfreeuid()
     * obtenir le plus petit UID disponible
     */

    function getfreeuid() {
        $gid = exec("sudo /usr/share/scripts/script.sh -u");
        return $gid;
    }

    /**
     * getgid($group)
     * obtenir GID en fonction du nom du groupe 
     */

    function getgid($group) {
        $ldapconngetgid = lda_connect(LDAP_ADMIN_DN,LDAP_ADMIN_PASS);
        $filter="(cn=" .$group. ")";
        $sr=ldap_search($ldapconngetgid, "ou=group," .LDAP_BASE, $filter);
        $info = ldap_get_entries($ldapconngetgid, $sr);
        if ($info['count']) {
            return (int) $info[0]["gidnumber"][0];
        } else {
            return -1;
        }
    }

    /**
     * getgroup($login)
     * obtenir le nom du groupe en fonction du login
     * particulier au l'organisation du serveur JPS
     */

    function getgroup($login) {
        $ldapconngetgroup = lda_connect(LDAP_ADMIN_DN,LDAP_ADMIN_PASS);
        $filter="(uid=" .$login. ")";
        $sr=ldap_search($ldapconngetgroup, LDAP_BASE, $filter);
        $info = ldap_get_entries($ldapconngetgroup, $sr);
        if ($info['count']) {
            $result = $info[0]['dn'];
            list ($foo,$mydomain,$foo2) = split(',',$result);
            list ($foo,$group) = split('=',$mydomain);
            return $group;
        } else {
            return -1;
        }
    }

    function is_uid($login) {
        $ldapconnisuid = Ldap::lda_connect(LDAP_ADMIN_DN,LDAP_ADMIN_PASS);
        $filter="(uid=" .$login. ")";
        $sr=ldap_search($ldapconnisuid, LDAP_BASE, $filter);
        $info = ldap_get_entries($ldapconnisuid, $sr);
        if (($info['count']) && ($info[0]['uid'][0] == $login )) {
            ldap_unbind($ldapconnisuid);
            return TRUE;
        } else {
            ldap_unbind($ldapconnisuid);
            $stack = array ("root","nobody","news","daemon","bin","sys","sync","postmaster","mailer-daemon",
                    "games","man","lp","mail","uucp","proxy","www-data","backup","list","irc","gnats","abuse",
                    "postfix","sshd","forquota","amavis","clamav","mysql","gcolpart","aanriot","log2mail");
            while (count($stack)) {
                $unixuid = array_shift($stack);
                if ( $login == $unixuid ) {
                    return TRUE;
                }
            }
            return FALSE;
        }
    }


    function is_what($login,$what) {
        $ldapconnisuid = Ldap::lda_connect(LDAP_ADMIN_DN,LDAP_ADMIN_PASS);
        $filter="(" .$what. "=" .$login. ")";
        $sr=ldap_search($ldapconnisuid, LDAP_BASE, $filter);
        $info = ldap_get_entries($ldapconnisuid, $sr);
        if ($info['count']) {
            ldap_unbind($ldapconnisuid);
            return $info['count'];
        }
        else
        {
            ldap_unbind($ldapconnisuid);
            return FALSE;
        }
    }

    function sha($pass) {
        return base64_encode(pack("H*", sha1($pass)));
    }

    // necessite php(4|5)-mhash
    function ssha($pass) {
        mt_srand((double)microtime()*1000000);
        $salt = mhash_keygen_s2k(MHASH_SHA1, $pass, substr(pack('h*', md5(mt_rand())), 0, 8), 4);
        return base64_encode(mhash(MHASH_SHA1, $pass.$salt).$salt);
    }

    // necessite Crypt/CHAP -> http://gcolpart.evolix.net/debian/php-crypt-chap/
    // inspire de lib/Driver/smbldap.php (Horde Password)
    function sambalm($pass) {
        $hash = new Crypt_CHAP_MSv2();
        $hash->password = $pass;
        return strtoupper(bin2hex($hash->lmPasswordHash()));
    }

    // necessite Crypt/CHAP -> http://gcolpart.evolix.net/debian/php-crypt-chap/
    // inspire de lib/Driver/smbldap.php (Horde Password)
    function sambant($pass) {
        $hash = new Crypt_CHAP_MSv2();
        $hash->password = $pass;
        return strtoupper(bin2hex($hash->ntPasswordHash()));
    }

}

?>

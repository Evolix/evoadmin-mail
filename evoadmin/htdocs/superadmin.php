<?php

/**
 * Listing of all domains
 *
 * Copyright (c) 2004-2006 Evolix - Tous droits reserves
 * $Id: superadmin.php,v 1.12 2009-09-02 17:22:13 gcolpart Exp $
 *
 * @author Gregory Colpart <reg@evolix.fr>
 * @version 1.0
 */

/*
 * Functions
 */

/**
 * Show domain details
 * @param string $domain
 * @return NULL
 */
function show_domaine_details($domain) {

    global $conf;

    print '<tr><td><a href="admin.php?domain='
        .$domain. '">' .$domain. '</a></td>';

    print '<td><b>' .getnumber($domain,'compte'). '</b></td>';
    if($conf['admin']['what'] == 3) {
       print '<td><b>' .getnumber($domain,'mail'). '</b></td>';
       print '<td><b>' .getnumber($domain,'smb'). '</b></td>';
    }
    if(($conf['admin']['what'] == 1) || ($conf['admin']['what'] == 3)) {
       print '<td><b>' .getnumber($domain,'alias'). '</b></td>';
    }
    if($conf['admin']['quota']) {
       print '<td>' .getquota($domain,'group'). '</td>';
    }


    print '<td>';

    // suppression possible que si utilisation de LDAP
    if ( $conf['domaines']['driver'] == 'ldap' ) {
        print '<a href="domaine.php?del=' .$domain. '">
            <img src="inc/suppr.png" /></a>';
    } else {
        print "Impossible";
    }
    print '</td></tr>';
}

/**
 * Path
 */
define('EVOADMIN_BASE','./');

/**
 * PHP cookies session
 */
session_name('EVOADMIN_SESS');
session_start();

if (isset($_SESSION['login'])) {

    /**
     * Requires
     */
    require_once EVOADMIN_BASE . 'common.php';

    include EVOADMIN_BASE . 'haut.php';

    $login = $_SESSION['login'];

    // pas de domaine/variable domaine sur superadmin.php
    unset($_SESSION['domain']);

    global $conf;

    // array with all domains with rights on
    $domaines = array();

    // If you are superadmin, you view all domains
    if (superadmin($login)) {

        // driver 'ldap'
        if ( $conf['domaines']['driver'] == 'ldap' ) {

            //TODO: foreach LDAP serveurs
            if ($conf['evoadmin']['cluster']) {
                $ldapconns = array();
                foreach ($ldap_servers as $server) {
                    array_push($ldapconns, Ldap::lda_connect(LDAP_ADMIN_DN,LDAP_ADMIN_PASS));
                }
            }
            else {
                $ldapconn = Ldap::lda_connect(LDAP_ADMIN_DN,LDAP_ADMIN_PASS);
            }

            if ($ldapconn) {

                // compatibilite anciens schemas
                if ($conf['evoadmin']['version'] == 1) {
                    $filter="(objectClass=ldapDomain)";
                } else {
                    $filter="(objectClass=postfixDomain)";
                }
                $sr=ldap_search($ldapconn, LDAP_BASE, $filter);
                $info = ldap_get_entries($ldapconn, $sr);

                for ($i=0;$i<$info["count"];$i++) {
                    // compatibilite anciens schemas
                    if ($conf['evoadmin']['version'] == 1) {
                        array_push($domaines,$info[$i]["domain"][0]);
                    } else {
                        array_push($domaines,$info[$i]["cn"][0]);
                    }
                }

                ldap_unbind($ldapconn);

            } else {
                print "<p class='error'>Erreur de connexion : $ldapconn</p>";
                EvoLog::log("LDAP connection failed");
            }

        // driver 'file'
        } elseif ( $conf['domaines']['driver'] == 'file' ) {

            $domaines = $conf['domaines']['file']['all'];
        }
    // If you are not superadmin...
    } elseif ( $conf['domaines']['driver'] == 'file' ) {
        // you view all if using driver 'file'
        $domaines = $conf['domaines']['file']['all'];
    } elseif ( $conf['domaines']['driver'] == 'ldap' ) {
        // you view only your domain if using driver 'ldap'
        // we select domain in your DN
        // thanks to http://www.physiol.ox.ac.uk/~trp/regexp.html
        if ($conf['evoadmin']['version'] <= 2) {
            $mydomain = preg_replace("/uid=" .$login. ",domain=((?:(?:[0-9a-zA-Z_\-]+)\.){1,}(?:[0-9a-zA-Z_\-]+)),"
                . LDAP_BASE ."/","$1",$_SESSION['dn']);
        }
        else {
            $mydomain = preg_replace("/uid=" .$login. ",cn=((?:(?:[0-9a-zA-Z_\-]+)\.){1,}(?:[0-9a-zA-Z_\-]+)),"
                . LDAP_BASE ."/","$1",$_SESSION['dn']);
        }

        array_push($domaines,$mydomain);
    }

    // alphanumerique sort before displaying domains
    sort($domaines);

    include EVOADMIN_BASE . 'debut.php';

        // with driver 'ldap', we can add a domain
        // TODO : retrict to superadmin guys
        if ( $conf['domaines']['driver'] == 'ldap' ) {
            print '<p><a href="domaine.php">
                Ajouter un domaine...</a></p>';
        }

    ?>
        <center>
        <h4>Liste des domaines administrables :</h4>

        <table width="500px" bgcolor="#ddd" border="1">
        <tr>
        <td><strong>Nom du domaine</strong></td>
        <td>Nombre de comptes</td>
        <?php if($conf['admin']['what'] == 3) { ?>
        <td>dont comptes mail</td>
        <td>dont comptes Samba</td>
        <?php } ?>
        <?php if(($conf['admin']['what'] == 1) || ($conf['admin']['what'] == 3)) { ?>
        <td>Nombre d'alias mail</td>
        <?php } ?>
        <?php if($conf['admin']['quota']) { ?>
        <td>Taille/Quota</td>
        <?php } ?>
        <td>Suppression du domaine</td>
        </tr>

        <?php

        // lignes avec les details sur les domaines
        foreach ($domaines as $domaine) {
            show_domaine_details($domaine);
        }

        print '</table></center>';
        print '<br />';

//if (isset($_SESSION['login']))
} else {

    header("location: auth.php\n\n");
    exit(0);
}

include(EVOADMIN_BASE . 'fin.php');

?>

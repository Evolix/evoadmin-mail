<?php

/**
 * Add/delete a domain
 *
 * Copyright (c) 2004-2006 Evolix - Tous droits reserves
 * $Id: domaine.php,v 1.2 2009-09-02 21:21:24 gcolpart Exp $
 *
 * @author Gregory Colpart <reg@evolix.fr>
 * @version 1.0
 */

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

    // $login var need for debut.php
    $login = $_SESSION['login'];

    include EVOADMIN_BASE . 'haut.php';
    include EVOADMIN_BASE . 'inc/add.js';
    include EVOADMIN_BASE . 'debut.php';

    if ( (!superadmin($login)) || ($conf['domaines']['driver'] != 'ldap') ) {

	print "<p class='error'>Vous n'avez pas les droits pour cette page</p>";
	EvoLog::log("Access denied on domaine.php");

	include EVOADMIN_BASE . 'fin.php';
	exit(1);
    }

    // Supprimer un domaine
    if ( isset($_GET['del']) ) {

        $domain = Html::clean($_GET['del']);
        
        if ( (isset($_GET['modif'])) && ($_GET['modif'] == 'yes')) {

            print "<center>";

            print "<p>Suppression $domain en cours...</p>";

            // TODO : Verifier que l'objet existe avant sa suppression
            //$ldapconn = Ldap::lda_connect(LDAP_ADMIN_DN,LDAP_ADMIN_PASS);
            //$sr = Ldap::lda_del($ldapconn,"domain=" .$domain. "," .$rdn);

            if ( $sr ) {
                // script suppression systeme
                //unix_del_dom($domain);

                // TODO : suppression comptes associes

                print "<p class='strong'>Suppression $domain effectu&eacute;e.</p>";

                EvoLog::log("Del domain ".$domain);

            } else {
                print "<p class='error>Erreur, suppression non effectu&eacute;e.</p>";
                EvoLog::log("Delete $domain failed");
            }

            print "</center>";
        
        } else {
            print "<center>"; 
            print "<p>Vous souhaitez effacer compl&egrave;tement le domaine <b>$domain</b>...<br />";
            print "Mais cette option n'est pas disponible par l'interface web.<br />";
            print "Veuillez prendre contact avec l'administrateur pour faire cela.</p>";
            //print "Tous les messages et param&egrave;tres seront d&eacute;finitivement perdus.</p>";
            //print "<a href='compte.php?del=$uid&modif=yes'>Confirmer la suppression</a>";
            print "</center>"; 
        }

    } else {

        // Ajouter un domaine
        if ( (isset($_GET['modif'])) && ($_GET['modif'] == 'yes')) {
        
            $domain = Html::clean($_POST['domain']); 

            print "<center>";
            print "Ajout en cours...";
          
            if (!$conf['domaines']['ldap']['virtual']) {

                if ( $conf['evoadmin']['version'] == 1) {

                    $info["domain"]=$domain;
                    $info["objectclass"][0] = "ldapDomain";
                    $info["postfixTransport"] = "local:";
                    $info["accountActive"] = (isset($_POST['isactive'])) ? "TRUE" : "FALSE";

                    $info2["cn"] = $domain;
                    $info2["objectclass"]="posixGroup";
                    // recuperer un uid number valide
                    // TODO : erreur si uid non compris entre 1000 et 29999
                    $info2["gidNumber"]= getfreegid();

                    $ldapconn = Ldap::lda_connect(LDAP_ADMIN_DN,LDAP_ADMIN_PASS);

                    // on teste si LDAP est content
                    if ( ldap_add($ldapconn,"domain=" .$domain. "," .LDAP_BASE, $info)
                        && ldap_add($ldapconn,"cn=" .$domain. ",ou=group," .LDAP_BASE, $info2) ) {

                        // script ajout systeme (TODO : quota)
                        //unix_add($uid,getgid($_SESSION['domain']));
                        print "<p class='strong'>Ajout effectu&eacute;.</p>";
                        EvoLog::log("Add domain ".$domain);

                        // notification par mail
                        domainnotify($domain); 

                    } else {
                        print "<p class='error'>Erreur, envoyez le message d'erreur
                            suivant &agrave; votre administrateur :</p>";
                        var_dump($info);
                        var_dump($info2);
                        EvoLog::log("Add $domain failed");
                    }
                } elseif ( $conf['evoadmin']['version'] == 2) {
                    // TODO : cf worldsat, etc.

                }
            } else {

                // Ajout d'un domaine virtuel

                $info["cn"]=$domain;
                $info["objectclass"][0] = "postfixDomain";
                $info["objectclass"][1] = "posixGroup";
                $info["postfixTransport"] = "virtual:";
                $info["isActive"] = (isset($_POST['isactive'])) ? "TRUE" : "FALSE";

                // recuperer un uid number valide
                // TODO : erreur si uid non compris entre 1000 et 29999
                $info["gidNumber"]= getfreegid();

                $ldapconn = Ldap::lda_connect(LDAP_ADMIN_DN,LDAP_ADMIN_PASS);

                // on teste si LDAP est content
                if (ldap_add($ldapconn,"cn=" .$domain. "," .LDAP_BASE, $info)) {

                    print "<p class='strong'>Ajout effectu&eacute;.</p>";
                    EvoLog::log("Add domain ".$domain);

                    // notification par mail
                    domainnotify($domain); 

                } else {
                    print "<p class='error'>Erreur, envoyez le message d'erreur
                        suivant &agrave; votre administrateur :</p>";
                    var_dump($info);
                    EvoLog::log("Add $domain failed");
                }

            }

        print "</center>";

        // Formulaire d'ajout d'un domaine
        } else {
            ?>
                <center>
                
                <h4>Ajout d'un domaine</h4>

            <form name="add"
                action="domaine.php?modif=yes"
                method="post">
 
            <p class="italic">Remplissez lez champs, ceux contenant [*] sont obligatoires.</p>

            <table>

            <tr><td align="right">Domaine [*] :</td>
            <td align="left"><input type="text" name="domain" tabindex='1' /></td></tr>

            <tr><td align="right">Activation globale :</td>
            <td align="left"><input type='checkbox' tabindex='2' 
                name='isactive' checked /></td></tr>

            <tr><td>&nbsp;</td><td align="left">
            <p><input type="submit" class="button" tabindex='3' 
                value="Valider" name="valider" /></p>
            </td></tr>


            </table>
            </form>

	       </center>
	   
        <?php
        }
    }

//if (isset($_SESSION['login'])) 
} else {
    header("location: auth.php\n\n");
    exit(0);
}

include EVOADMIN_BASE . 'fin.php';

?>

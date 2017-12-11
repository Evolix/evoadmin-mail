<?php

/**
 * Add/Modify an alias
 *
 * Copyright (c) 2004-2006 Evolix - Tous droits reserves
 * $Id: alias.php,v 1.12 2009-02-18 23:19:29 gcolpart Exp $
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

if (isset($_SESSION['login']))
{
    require_once EVOADMIN_BASE . 'lib/common.php';

    include EVOADMIN_BASE . 'inc/haut.php';

    $login = $_SESSION['login'];
    $rdn = $_SESSION['rdn'];

    include EVOADMIN_BASE . 'inc/debut.php';

    if (isset($_GET['view'])) {

        $cn = Html::clean($_GET['view']);

        $ldapconn = Ldap::lda_connect(LDAP_ADMIN_DN,LDAP_ADMIN_PASS);

        $filter="(cn=$cn)";
        $sr=ldap_search($ldapconn, $rdn, $filter);
        $info = ldap_get_entries($ldapconn, $sr);

        if ( (isset($_GET['modif'])) && ($_GET['modif'] == 'yes')) {

            print "<center>";
            print "<p>Modification en cours...</p>";

            // on obtient une table avec les nouveaux champs mailacceptinggeneralid
            // TODO : if onlyone = false, verifier le domaine !!
            $count = array_shift($info[0]["mailacceptinggeneralid"]);

            // in multi-domains mode, we force @domain
            if (!$conf['domaines']['onlyone']) {
                // add @domain for each element
                array_walk($_POST['mailaccept'],'adddomain');
            }

            $newmailaccept[0] = array_pop($_POST['mailaccept']);
            $newmailaccept[1] = array_pop($_POST['mailaccept']);
            $newmailaccept[2] = array_pop($_POST['mailaccept']);
            if ( ($newmailaccept[0] != NULL) || ($newmailaccept[1] != NULL) ||
                    ($newmailaccept[2] != NULL) || 
                    array_diff($info[0]["mailacceptinggeneralid"],$_POST['mailaccept']) ) {
                $new["mailacceptinggeneralid"] = $_POST['mailaccept'];
                $new["mailacceptinggeneralid"][$count]= $newmailaccept[0];
                $new["mailacceptinggeneralid"][$count+1]= $newmailaccept[1];
                $new["mailacceptinggeneralid"][$count+2]= $newmailaccept[2];

                // on vire les valeurs nulles
                sort($new["mailacceptinggeneralid"]);
                while ( $new["mailacceptinggeneralid"][0] == NULL ) {
                    array_shift($new["mailacceptinggeneralid"]);

                    // on evite une boucle infinie
                    if ( count($new["mailacceptinggeneralid"]) == 0 ) {
                        print "Erreur, vous devez avoir au moins un mail entrant\n";
                        exit(1);
                    }
                }
            }

            // idem avec maildrop
            $count = array_shift($info[0]["maildrop"]);
            $newmaildrop[0] = array_pop($_POST['maildrop']);
            $newmaildrop[1] = array_pop($_POST['maildrop']);
            $newmaildrop[2] = array_pop($_POST['maildrop']);
            if ( ($newmaildrop[0] != NULL) | ($newmaildrop[1] != NULL) |
                    ($newmaildrop[2] != NULL) |
                    array_diff($info[0]["maildrop"],$_POST['maildrop']) ) {
                $new["maildrop"] = $_POST['maildrop'];
                $new["maildrop"][$count]= $newmaildrop[0];
                $new["maildrop"][$count+1]= $newmaildrop[1];
                $new["maildrop"][$count+2]= $newmaildrop[2];

                // on vire les valeurs nulles
                sort($new["maildrop"]);
                while ( $new["maildrop"][0] == NULL ) {
                    array_shift($new["maildrop"]);

                    // on evite une boucle infinie
                    if ( count($new["maildrop"]) == 0 ) {
                        print "Erreur, vous devez avoir au moins une redirection.\n";
                        exit(1);
                    }
                }
            }

            $postisactive = (isset($_POST['isactive']) ? 'TRUE' : 'FALSE');

            // Compatibilite anciens schemas LDAP
            //if ($conf['evoadmin']['version'] == 1) {
            //    if ( $info[0]["accountactive"][0] != $postisactive ) {
            //        $new["accountActive"] = $postisactive;
            //    }
            //} else {
                if ( $info[0]["isactive"][0] != $postisactive ) {
                    $new["isActive"] = $postisactive;
                }
            //}

            // if $new not null, set modification
            if ( (isset($new)) && ($new != NULL) ) {
                $sr=ldap_modify($ldapconn,"cn=" .$cn. ",".$rdn,$new);

                // Si LDAP est content, c'est bon :)
                if ( $sr ) {
                    print "<p class='strong'>Modifications effectu&eacute;es.</p>";
                    print "<a href='alias.php?view=$cn'>Voir l'alias modifi&eacute;</p>";
                } else {
                    print "<p class='error'>Erreur, envoyez le message d'erreur
                        suivant a votre administrateur :</p>";
                    var_dump($new);
                    Evolog::log("Modify error of $cn by $login");
                }

            } else {
                print "<p class='strong'>Aucune modification n&eacute;cessaire.</p>";
            }
	    
	    print "</center>";

        } else {

            $filter="(&(cn=$cn)(objectClass=mailAlias))";
            $sr=ldap_search($ldapconn, $rdn, $filter);
            $info = ldap_get_entries($ldapconn, $sr);

            // On verifie que le compte existe bien
            if ( $info['count'] != 1 ) {
                print "<p class='error'>Erreur, alias inexistant</p>";
                EvoLog::log("alias $cn unknown");
                exit(1);
            } 

            print "<center>\n";
            print "<h4>Modification de l'alias $cn</h4>\n";

            print "<form name='add'
                action='alias.php?view=$cn&modif=yes'
                method='post'>\n";

            print "<table>\n";

            print "<tr><td colspan='2'>";
            print "<p class='italic'>Ajoutez/modifiez/supprimez les mails accept&eacute;s en entr&eacute;e).<br />
                Un minimum d'un mail est requis. M&ecirc;mes instructions<br />
                pour les redirections (compte(s) dans le(s)quel(s) est/sont d&eacute;livr&eacute;(s) les mails).
                </p>";
            print "</td></tr>";

            // compteur pour les tabindex
            $tab=1;

            for ($i=0;$i<$info[0]["mailacceptinggeneralid"]['count'];$i++) {

                if (!$conf['domaines']['onlyone']) {
                    $info[0]['mailacceptinggeneralid'][$i] =
                        preg_replace("/@".$_SESSION['domain']."/",'',$info[0]['mailacceptinggeneralid'][$i]);
                }

                print "<tr><td align='right'>Mail accept&eacute; en entr&eacute;e :</td>
                    <td align='left'><input type='text' name='mailaccept[$i]'  tabindex='" .$tab++. "'
                    size='30' value='".$info[0]['mailacceptinggeneralid'][$i]."' />\n";
                    if (!$conf['domaines']['onlyone']) {
                        print "@" .$_SESSION['domain'];
                    }

                    print "</td></tr>\n";
            }

            print "<tr><td align='right'>Cr&eacute;ation d'un nouveau mail accept&eacute; en entr&eacute;e :</td>
                <td align='left'><input type='text' name='mailaccept[" .$i++. "]'
                size='30' tabindex='" .$tab++. "' />\n";
            if (!$conf['domaines']['onlyone']) {
                print "@" .$_SESSION['domain'];
            }
            print "</td></tr>\n";

            print "<tr><td align='right'>Cr&eacute;ation d'un nouveau mail accept&eacute; en entr&eacute;e :</td>
                <td align='left'><input type='text' name='mailaccept[" .$i++. "]'
                size='30' tabindex='" .$tab++. "' />\n";
            if (!$conf['domaines']['onlyone']) {
                print "@" .$_SESSION['domain'];
            }
            print "</td></tr>\n";

            print "<tr><td align='right'>Cr&eacute;ation d'un nouveau mail accept&eacute; en entr&eacute;e :</td>
                <td align='left'><input type='text' name='mailaccept[" .$i++. "]'
                size='30' tabindex='" .$tab++. "' />\n";
            if (!$conf['domaines']['onlyone']) {
                print "@" .$_SESSION['domain'];
            }
            print "</td></tr>\n";


            for ($i=0;$i<$info[0]["maildrop"]['count'];$i++) {
                print "<tr><td align='right'>Mails entrants redirig&eacute;s vers :</td>
                    <td align='left'><input type='text' name='maildrop[$i]'
                    size='30' value='" .$info[0]['maildrop'][$i]. "' tabindex='" .$tab++. "' />
                    </td></tr>\n";
            }

            print "<tr><td align='right'>Nouvelle redirection vers :</td>
                <td align='left'><input type='text' name='maildrop[" .$i++. "]'
                size='30' tabindex='" .$tab++. "' /></td></tr>\n";
            print "<tr><td align='right'>Nouvelle redirection vers :</td>
                <td align='left'><input type='text' name='maildrop[" .$i++. "]'
                size='30' tabindex='" .$tab++. "' /></td></tr>\n";
            print "<tr><td align='right'>Nouvelle redirection vers :</td>
                <td align='left'><input type='text' name='maildrop[" .$i++. "]'
                size='30' tabindex='" .$tab++. "' /></td></tr>\n";

            print "<tr><td colspan='2'>";
            print "<p class='italic'>Activer/d&eacute;sactiver l'alias</p>";
            print "</td></tr>";

            // Compatibilite anciens schemas LDAP
            //if ($conf['evoadmin']['version'] == 1) {
            //    $isactive= ($info[0]["accountactive"][0] == 'TRUE') ? 'checked' : '';
            //} else {
                $isactive= ($info[0]["isactive"][0] == 'TRUE') ? 'checked' : '';
            //}
            print "<tr><td align='right'>Alias actif :</td>
                <td align='left'><input type='checkbox' name='isactive'
                $isactive tabindex='" .$tab++. "' /></td></tr>\n";

            print "<tr><td>&nbsp,</td><td align='left'>";
            print "<p><input type='submit' class='button' 
                value='Valider' name='valider' tabindex='" .$tab++. "' /></p>\n";
            print "</td></tr>";
                
            print "</table>\n";
            print '</form>';
        }

    } elseif ( isset($_GET['del']) ) {

        $cn = Html::clean($_GET['del']);
        
        if ( (isset($_GET['modif'])) && ($_GET['modif'] == 'yes')) {

            print "<center>";

            print "<p>Suppression $cn en cours...</p>";

            // TODO : Verifier que l'objet existe avant sa suppression
            $ldapconn = Ldap::lda_connect(LDAP_ADMIN_DN,LDAP_ADMIN_PASS);
            $sr = Ldap::lda_del($ldapconn,"cn=" .$cn. "," .$rdn);

            if ( $sr ) {

                print "<p class='strong'>Suppression $cn effectu&eacute;e.</p>";
                
                EvoLog::log("Del alias ".$cn);

            } else {
                print "<p class='error>Erreur, suppression non effectu&eacute;e.</p>";
                EvoLog::log("Delete $cn failed");
            }

            print "</center>";
        
        } else {
            print "<center>"; 
            print "<p>Vous allez effacer l'alias <b>$cn</b>...<br />";
            print "<a href='alias.php?del=$cn&modif=yes'>Confirmer la suppression</a>";
            print "</center>"; 
        }

    } else {

        if ( (isset($_GET['modif'])) && ($_GET['modif'] == 'yes')) {

            $cn = Html::clean(Html::purgeaccents(utf8_decode($_POST['cn']))); 

            $aliases = $_POST['alias'];

            // in multi-domains mode, we force @domain
            if (!$conf['domaines']['onlyone']) {
                // add @domain for each element
                array_walk($aliases,'adddomain');
            }

            $maildrop = $_POST['maildrop'];

            print '<center>';
            print "<p>Ajout en cours</p>";

            // on vire les valeurs nulles
            sort($aliases);
            sort($maildrop);
            // TODO : if driver = ldap, verifier le domaine !!
            while ( $aliases[0] == NULL ) {
                array_shift($aliases);

                // on evite une boucle infinie
                if ( count($aliases) == 0 ) {
                    print "Erreur, vous devez avoir au moins un alias.\n";
                    exit(1);
                }
            }
            while ( $maildrop[0] == NULL ) {
                array_shift($maildrop);

                // on evite une boucle infinie
                if ( count($maildrop) == 0 ) {
                    print "Erreur, vous devez avoir au moins une redirection.\n";
                    exit(1);
                }
            }

            $info["cn"]=$cn;
            $info["objectclass"][0] = "mailAlias";
            $info["isActive"] = ($_POST['isactive']) ? "TRUE" : "FALSE";

            // Compatibilite anciens schemas LDAP
            if ($conf['evoadmin']['version'] == 1) {
                $info["objectclass"][1] = "inetOrgPerson";
                $info["onlyAlias"] = "TRUE";
                $info["sn"]=$cn;
                //$info["accountActive"] = ($_POST['isactive']) ? "TRUE" : "FALSE";
            }

            $info["mailacceptinggeneralid"] = $aliases;
            $info["maildrop"] = $maildrop;

            $ldapconn = Ldap::lda_connect(LDAP_ADMIN_DN,LDAP_ADMIN_PASS);
            $sr=ldap_add($ldapconn,"cn=" .$cn. "," .$rdn, $info);

            // on teste si LDAP est content
            if ( $sr ) {
                print "<p class='strong'>Ajout effectu&eacute;.</p>";
                    print "<a href='alias.php?view=$cn'>Voir l'alias ajout&eacute;</p>";
                EvoLog::log("Add alias ".$cn);
            } else {
                print "<p class='error'>Erreur, envoyez le message d'erreur
                    suivant a votre administrateur :</p>";
                var_dump($info);
                EvoLog::log("Add alias $cn failed");
            }

            print "</center>";

        } else {
            ?>
                <center>
                
                <h4>Ajout d'un alias</h4>

            <form name="add"
                action="alias.php?modif=yes"
                method="post">
 
            <p class="italic">Remplissez lez champs.</p>

            <table>

            <tr><td align="right">Nom (unique) de l'alias :</td>
            <td align="left"><input type='text' name='cn' tabindex='1' /></td></tr>
            
            <tr><td align="right">Alias :</td>
            <td align="left"><input type='text' name='alias[0]' tabindex='2' />
            <?php
                if (!$conf['domaines']['onlyone']) {
                   print "@" .$_SESSION['domain'];
                }
            ?>
            </td></tr>

            <tr><td align="right">Alias :</td>
            <td align="left"><input type='text' name='alias[1]' tabindex='3' />
            <?php
                if (!$conf['domaines']['onlyone']) {
                   print "@" .$_SESSION['domain'];
                }
            ?>
            </td></tr>
           
            <tr><td align="right">Alias :</td>
            <td align="left"><input type='text' name='alias[2]' tabindex='4' />
            <?php
                if (!$conf['domaines']['onlyone']) {
                   print "@" .$_SESSION['domain'];
                }
            ?>
            </td></tr>
          
            <tr><td align="right">Alias :</td>
            <td align="left"><input type='text' name='alias[3]' tabindex='5' />
            <?php
                if (!$conf['domaines']['onlyone']) {
                   print "@" .$_SESSION['domain'];
                }
            ?>
            </td></tr>
         
            <tr><td align="right">Alias :</td>
            <td align="left"><input type='text' name='alias[4]' tabindex='6' />
            <?php
                if (!$conf['domaines']['onlyone']) {
                   print "@" .$_SESSION['domain'];
                }
            ?>
            </td></tr>

            <tr><td align="right">Redirection :</td>
            <td align="left"><input type='text' name='maildrop[5]' tabindex='7' /></td></tr>

            <tr><td align="right">Redirection :</td>
            <td align="left"><input type='text' name='maildrop[1]' tabindex='8' /></td></tr>
            
            <tr><td align="right">Redirection :</td>
            <td align="left"><input type='text' name='maildrop[2]' tabindex='9' /></td></tr>
            
            <tr><td align="right">Redirection :</td>
            <td align="left"><input type='text' name='maildrop[3]' tabindex='10' /></td></tr>
            
            <tr><td align="right">Redirection :</td>
            <td align="left"><input type='text' name='maildrop[4]' tabindex='11' /></td></tr>
           
            <tr><td colspan="2">
            <p class="italic">Activer/d&eacute;sactiver l'alias</p>
            </td></tr>

            <tr><td align="right">Alias actif :</td>
            <td align="left"><input type='checkbox' tabindex='10' 
                name='isactive' checked /></td></tr>
 

            <tr><td>&nbsp;</td><td align="left">
            <p><input type="submit" class="button" tabindex='15' 
                value="Valider" name="valider" /></p>
            </td></tr>

            </table>
            </form>

                </center>

        <?php
        }
    }

} //if (isset($_SESSION['login'])) 
else
{
    header("location: auth.php\n\n");
    exit(0);
}

include EVOADMIN_BASE . 'inc/fin.php';

?>


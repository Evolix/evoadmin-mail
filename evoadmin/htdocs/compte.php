<?php

/**
 * Add/Modify an account
 *
 * Copyright (c) 2004-2006 Evolix - Tous droits reserves
 * $Id: compte.php,v 1.34 2009-09-02 23:10:52 gcolpart Exp $
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

    // $login var need for debut.php
    $login = $_SESSION['login'];

    /**
     * Requires
     */
    require_once EVOADMIN_BASE . 'common.php';

    include EVOADMIN_BASE . 'haut.php';
    include EVOADMIN_BASE . 'inc/add.js';
    include EVOADMIN_BASE . 'debut.php';

    $rdn = $_SESSION['rdn'];
    $group_dn = "ou=group,".LDAP_BASE;

    /**
     * Account modification
     */
    if (isset($_GET['view'])) {

        $uid = Html::clean($_GET['view']);

        $ldapconn = Ldap::lda_connect(LDAP_ADMIN_DN,LDAP_ADMIN_PASS);

        $filter="(uid=$uid)";
        $sr=ldap_search($ldapconn, $rdn, $filter);
        $info = ldap_get_entries($ldapconn, $sr);

        $cn = $info[0]["cn"][0];
        $sn = $info[0]["sn"][0];
        $gid = $info[0]["gidnumber"][0];
        // optional
        $mail = array_key_exists("mail",$info[0]) ? $info[0]["mail"][0] : '';

        // Cas d'un compte Samba
        if (($conf['admin']['what'] == 2) || ($conf['admin']['what'] == 3)) {

            $displayname = $info[0]["displayname"][0];
            $sambagroup = array_search($gid,getsambagroups('unix'));
            if (!$sambagroup) {
                $sambagroup = "!!undefined!!";
            }
        }

        /**
         * Set account modification
         */
        if ( (isset($_GET['modif'])) && ($_GET['modif'] == 'yes')) {

            print "<center>";
            print "<p>Modification en cours...</p>";

            // TODO : contraintes sur cn, sn, etc.
            if ( $cn != Html::clean($_POST['cn']) ) {
                $new["cn"] = Html::justclean(Html::purgeaccents(utf8_decode($_POST['cn'])));

                if ($conf['evoadmin']['version'] == 1) {
                    $new["sn"] = $new["cn"];
                }

            }

            if ( ($conf['evoadmin']['version'] > 1) && (!$conf['domaines']['ldap']['virtual']) && ( $sn != Html::clean($_POST['sn']) ) ) {
                $new["sn"] = Html::justclean(Html::purgeaccents(utf8_decode($_POST['sn'])));
            }

            if ( (!$conf['domaines']['ldap']['virtual']) && ( $mail != Html::clean($_POST['mail']) )) {
                $new["mail"] = Html::clean($_POST['mail']);
            }

            if ( $_POST['pass1'] != '' ) {
                if ( $_POST['pass1'] != $_POST['pass2'] ) {
                    print "<p class='error'>Erreur, vous avez tap&eacute;
                        deux mots de passe diff&eacute;rents</p>";
                    EvoLog::log("Reinit password failed for $uid by $login");
                    exit(1);
                }

                if ( Auth::badpassword($_POST['pass1']) ) {
                    print "<p class='error'>Erreur, mot de passe invalide
                        (trop court ou avec des caracteres incorrects)</p>";
                    EvoLog::log("Set password failed for $uid by $login");
                    exit(1);
                }

                $new["userPassword"] = "{SSHA}".Ldap::ssha($_POST['pass1']);

                // Cas d'un compte Samba
                if (($conf['admin']['what'] == 2) || ($conf['admin']['what'] == 3)) {

                    $new["sambaPwdLastSet"] = strtotime("now");
                    $new["sambaLMPassword"] = Ldap::sambalm($_POST['pass1']);
                    $new["sambaNTPassword"] = Ldap::sambant($_POST['pass1']);
                    $new["shadowLastChange"] = floor(strtotime("now")/(3600*24));
                }

            }
                if (($conf['admin']['what'] == 2) || ($conf['admin']['what'] == 3)) {

                $ldapconn = Ldap::lda_connect(LDAP_ADMIN_DN,LDAP_ADMIN_PASS);

                $filter = "(memberUid=$uid)";
                $attr = array("cn");

                $sr=ldap_search($ldapconn, $group_dn, $filter, $attr);
                $result = ldap_get_entries($ldapconn, $sr);
                $arraycn = array();

                for ($i=0; $i < $result["count"] ; $i++)
                {
                    $arraycn[] = $result[$i]["cn"][0];
                }

                if((isset($_POST['smbgroupsecondaire']) && !empty($_POST['smbgroupsecondaire'])) || (isset($_POST['smbgroupsecondaire']) == NULL)){
                    if ($_POST['smbgroupsecondaire'] == NULL ){
                        $arrayGroupes = [];
                        if ($arrayGroupes != $arraycn)
                        {
                            $new2["cntosuppr"] = array_diff($arraycn, $arrayGroupes);
                        }
                    }
                    else {
                        $arrayGroupes = $_POST['smbgroupsecondaire'];
                        foreach($arrayGroupes as $nameGroupe){
                            $arrayCnNew[] = $nameGroupe;
                        }
                        if ($arrayCnNew != $arraycn)
                        {
                            $new2["cntoadd"] = array_diff($arrayCnNew, $arraycn);
                            $new2["cntosuppr"] = array_diff($arraycn, $arrayCnNew);
                        }
                    }
                }
            }

            $postisactive = (isset($_POST['isactive']) ? 'TRUE' : 'FALSE');
            if ( $info[0]["isactive"][0] != $postisactive ) {
                $new["isActive"] = $postisactive;
            }

            $postisadmin = (isset($_POST['isadmin']) ? 'TRUE' : 'FALSE');
            if ( $info[0]["isadmin"][0] != $postisadmin ) {
                $new["isAdmin"] = $postisadmin;
            }

            if ($_POST['loginshell'] != $info[0]['loginshell'][0]) {
                $new["loginShell"] = Html::clean($_POST['loginshell']);
            }

            // only for mail mode
            if (($conf['admin']['what'] == 1) || ($conf['admin']['what'] == 3)) {

                $postaccountactive = (isset($_POST['accountactive']) ? 'TRUE' : 'FALSE');
                if ( $info[0]["accountactive"][0] != $postaccountactive ) {
                    $new["accountActive"] = $postaccountactive;
                }

                $postauthsmtpactive = (isset($_POST['authsmtpactive']) ? 'TRUE' : 'FALSE');
                if ( $info[0]["authsmtpactive"][0] != $postauthsmtpactive ) {
                    $new["authsmtpActive"] = $postauthsmtpactive;
                }

                $postwebmailactive = (isset($_POST['webmailactive']) ? 'TRUE' : 'FALSE');
                if ( $info[0]["webmailactive"][0] != $postwebmailactive ) {
                    $new["webmailActive"] = $postwebmailactive;
                }

                $postcourieractive = (isset($_POST['courieractive']) ? 'TRUE' : 'FALSE');
                if ( $info[0]["courieractive"][0] != $postcourieractive ) {
                    $new["courierActive"] = $postcourieractive;
                }

                // on obtient une table avec les nouveaux champs mailacceptinggeneralid
                // TODO : if driver == ldap, verifier le domaine !!
                $count = array_shift($info[0]["mailacceptinggeneralid"]);

                // Compatibilite anciens schemas LDAP et mode "virtuel"
                if (($conf['evoadmin']['version'] == 1) || ($conf['domaines']['ldap']['virtual'])) {
                    // add @domain for each element
                    array_walk($_POST['mailaccept'],'adddomain');
                }

                $newmailaccept = array_pop($_POST['mailaccept']);
                if ( ($newmailaccept != NULL) |
                        array_diff($info[0]["mailacceptinggeneralid"],$_POST['mailaccept']) ) {
                    $new["mailacceptinggeneralid"] = $_POST['mailaccept'];
                    $new["mailacceptinggeneralid"][$count]= $newmailaccept;

                    // on vire les valeurs nulles en triant puis supprimant les premieres valeurs
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
                $newmaildrop = array_pop($_POST['maildrop']);
                if ( ($newmaildrop != NULL) |
                        array_diff($info[0]["maildrop"],$_POST['maildrop']) ) {
                    $new["maildrop"] = $_POST['maildrop'];
                    $new["maildrop"][$count]= $newmaildrop;

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

            }

            // only for samba mode
            if (($conf['admin']['what'] == 2) || ($conf['admin']['what'] == 3)) {

                $postsmbactive = (isset($_POST['smbactive']) ? 'TRUE' : 'FALSE');
                if ( $info[0]["smbactive"][0] != $postsmbactive ) {
                    $new["smbActive"] = $postsmbactive;
                }

                if ( $displayname != Html::clean($_POST['displayname']) ) {
                    $new["displayname"] = Html::clean($_POST['displayname']);
                }
            }

            // if $new not null, set modification
            if ((isset($new)) || (isset($new2))) {

                if ((isset($new)))
                {
                    $sr=ldap_modify($ldapconn,"uid=" .$uid. ",".$rdn,$new);
                }

                if(count($new2["cntoadd"]) > 0)
                {
                    foreach($new2["cntoadd"] as $nameGroupe){
                        $entry_groupe["memberUid"] = $uid;
                        $addGroupe = ldap_mod_add($ldapconn, "cn=".$nameGroupe.",".$group_dn, $entry_groupe);
                    }
                }
                if(count($new2["cntosuppr"]) > 0)
                {
                    foreach($new2["cntosuppr"] as $nameGroupe){
                        $remove_groupe["memberUid"] = $uid;
                        $rmGroupe = ldap_mod_del($ldapconn, "cn=".$nameGroupe.",".$group_dn, $remove_groupe);
                    }
                }

                // Si LDAP est content, c'est bon :)
                    if (!$sr && !$addGroupe && !$rmGroupe) {
                    print "<p class='error'>Erreur, envoyez le message d'erreur
                        suivant &agrave; votre administrateur :</p>";
                    Evolog::log("Modify error of $uid by $login");
                    } else {
                    print "<p class='strong'>Modifications effectu&eacute;es.</p>";
                    print "<a href='compte.php?view=$uid'>Voir le compte modifi&eacute;</a>";
                }
            } else {
                print "<p class='strong'>Aucune modification n&eacute;cessaire.</p>";
            }

	    print "</center>";

        /*
         * Formular for account modification
         */
        } else {

            $filter="(uid=$uid)";
            $sr=ldap_search($ldapconn, $rdn, $filter);
            $info = ldap_get_entries($ldapconn, $sr);

            // On verifie que le compte existe bien
            if ( $info['count'] != 1 ) {
                print "<p class='error'>Erreur, compte inexistant</p>";
                EvoLog::log("login $uid unknown");
                exit(1);
            }

            print "<center>\n";
            print "<h4>Modification du compte $uid</h4>\n";

            print"<p class='italic'>Modifiez les champs que vous d&eacute;sirez changer.<br />
                [*] indique ceux qui ne doivent pas &ecirc;tre nuls.<br />
                Vous pouvez r&eacute;initialiser le mot de passe si besoin.</p>";

            print "<form name='add'
                action='compte.php?view=$uid&modif=yes'
                method='post'>\n";

            print "<table>\n";


            // Compatibilite anciens schemas LDAP ou mode "virtuel"
            if (($conf['evoadmin']['version'] != 1) && (!$conf['domaines']['ldap']['virtual'])) {

                print "<tr><td align='right'>Nom [*] :</td>
                    <td align='left'><input type='text' name='sn' tabindex='2'
                    value='$sn' /></td></tr>\n";
            }

            print "<tr><td align='right'>Nom Complet [*] :</td>
                <td align='left'><input type='text' name='cn' tabindex='1'
                value='$cn' /></td></tr>\n";

            print "<tr><td align='right'>Nouveau mot de passe :</td>
                <td align='left'><input type='password' name='pass1' tabindex='3' /></td></tr>\n";
            print "<tr><td align='right'>Confirmation du mot de passe :</td>
                <td align='left'><input type='password' name='pass2' tabindex='4' /></td></tr>\n";

            // Compatibilite anciens schemas LDAP
            if ($conf['evoadmin']['version'] == 1) {

                print "<tr><td align='right'>Mail principal :";
                print "</td><td align='left'>$mail</td></tr>\n";
                print "<input type='hidden' name='mail' value='$mail' />";

            } elseif (!$conf['domaines']['ldap']['virtual']) {
                print "<tr><td align='right'>Mail annonc&eacute; dans l'annuaire ";
                print " :</td><td align='left'><input type='text' name='mail' size='30'
                    value='$mail' tabindex='5' /></td></tr>\n";
            }

            // count for tabindex
            $tab=6;

            // only for samba mode
            if (($conf['admin']['what'] == 2) || ($conf['admin']['what'] == 3)) {
                $filter = "(memberUid=$uid)";
                $attr = array("cn");

                $sr=ldap_search($ldapconn, $group_dn, $filter, $attr);
                $result = ldap_get_entries($ldapconn, $sr);
                $arraycn = array();

            print "<tr><td colspan='2'>";
            print "<p class='italic'>Modification pour Samba</p>";
            print "</td></tr>";

            print "<tr><td align='right'>Nom dans Samba :</td>
                <td align='left'><input type='text' name='displayname' tabindex='" .$tab++. "'
                value='$displayname' /></td></tr>\n";

            print '
            <tr>
                <td align="right">Shell :</td>
                <td align="left">
                    <input type="text" name="loginshell" value="'
                        . $info[0]['loginshell'][0] . '" />
                </td>
            </tr>';

            print "<tr><td align='right'>Groupe Samba primaire :</td>
                <td align='left'>$sambagroup</td></tr>\n";
                print '<tr><td align="right">Groupe Samba secondaire :</td>
                <td align="left"><select style="margin-top:5px;" name="smbgroupsecondaire[]" multiple size=6>';

                $sambagroups = getsambagroups('smb');
                foreach ($sambagroups as $key=>$value) {
                        print "<option value='" . $key . "'";
                        for ($i=0; $i < $result["count"] ; $i++)
                        {
                            $arraycn[] = $result[$i]["cn"][0];
                            if($key == $_SESSION['domain'] || in_array($key, $arraycn)) {
                            print ' selected="selected"';
                            }
                        }
                        print "> $key </option>\n";
                }

            print "</select>";

            }

            // only for mail mode
            if (($conf['admin']['what'] == 1) || ($conf['admin']['what'] == 3)) {

                print "<tr><td colspan='2'>";
                print "<p class='italic'>Ajoutez/modifiez/supprimez les alias (mails accept&eacute;s en entr&eacute;e).<br />
                    Un minimum d'un alias est requis. M&ecirc;mes instructions<br />
                    pour les redirections (compte(s) dans le(s)quel(s) est/sont d&eacute;livr&eacute;(s) les mails).
                </p>";
                print "</td></tr>";


                for ($i=0;$i<$info[0]["mailacceptinggeneralid"]['count'];$i++) {

                    if (!$conf['domaines']['onlyone']) {
                        $info[0]['mailacceptinggeneralid'][$i] =
                            ereg_replace('@'.$_SESSION['domain'],'',$info[0]['mailacceptinggeneralid'][$i]);
                    }

                    print "<tr><td align='right'>Mail accept&eacute; en entr&eacute;e :</td>
                        <td align='left'><input type='text' name='mailaccept[$i]'  tabindex='" .$tab++. "'
                        size='30' value='".$info[0]['mailacceptinggeneralid'][$i]."' />";

                    if (!$conf['domaines']['onlyone']) {
                        print "@" .$_SESSION['domain'];
                    }

                    print "</td></tr>\n";
                }

                print "<tr><td align='right'>Cr&eacute;ation d'un nouveau mail accept&eacute; en entr&eacute;e :</td>
                    <td align='left'><input type='text' name='mailaccept[$i]'
                    size='30' tabindex='" .$tab++. "' />";
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
                    <td align='left'><input type='text' name='maildrop[$i]'
                    size='30' tabindex='" .$tab++. "' /></td></tr>\n";
            }

            print "<tr><td colspan='2'>";
            print "<p class='italic'>Modifiez les autorisations du compte si besoin.</p>";
            print "</td></tr>";

            $isactive= ($info[0]["isactive"][0] == 'TRUE') ? 'checked' : '';
            print "<tr><td align='right'>Activation globale :</td>
                <td align='left'><input type='checkbox' name='isactive'
                $isactive tabindex='" .$tab++. "' /></td></tr>\n";

            $isadmin= ($info[0]["isadmin"][0] == 'TRUE') ? 'checked' : '';
            print "<tr><td align='right'>Compte admin:</td>
                <td align='left'><input type='checkbox' name='isadmin'
                $isadmin tabindex='" .$tab++. "' /></td></tr>\n";

            // only for samba mode
            if (($conf['admin']['what'] == 2) || ($conf['admin']['what'] == 3)) {
                $smbactive= ($info[0]["smbactive"][0] == 'TRUE') ? 'checked' : '';
                print "<tr><td align='right'>Compte Samba actif :</td>
                    <td align='left'><input type='checkbox' name='smbactive'
                    $smbactive tabindex='" .$tab++. "' /></td></tr>\n";
            }

            // only for mail mode
            if (($conf['admin']['what'] == 1) || ($conf['admin']['what'] == 3)) {

                $accountactive= ($info[0]["accountactive"][0] == 'TRUE') ? 'checked' : '';
                print "<tr><td align='right'>Compte mail actif :</td>
                    <td align='left'><input type='checkbox' name='accountactive'
                    $accountactive tabindex='" .$tab++. "' /></td></tr>\n";

                            $courieractive= ($info[0]["courieractive"][0] == 'TRUE') ? 'checked' : '';
                print "<tr><td align='right'>Utilisation POP/IMAP :</td>
                    <td align='left'><input type='checkbox' name='courieractive'
                    $courieractive tabindex='" .$tab++. "' /></td></tr>\n";

                $webmailactive= ($info[0]["webmailactive"][0] == 'TRUE') ? 'checked' : '';
                print "<tr><td align='right'>Webmail actif :</td>
                    <td align='left'><input type='checkbox' name='webmailactive'
                    $webmailactive tabindex='" .$tab++. "' /></td></tr>\n";

                $authsmtpactive= ($info[0]["authsmtpactive"][0] == 'TRUE') ? 'checked' : '';
                print "<tr><td align='right'>Authentification SMTP :</td>
                    <td align='left'><input type='checkbox' name='authsmtpactive'
                    $authsmtpactive tabindex='" .$tab++. "' /></td></tr>\n";

            }

            print "<tr><td>&nbsp,</td><td align='left'>";
            print "<p><input type='submit' class='button' onclick='return submit_add();'
                value='Valider' name='valider' tabindex='" .$tab++. "' /></p>\n";
            print "</td></tr>";

            print "</table>\n";
            print '</form>';
        }

    /**
     * Delete account
     */
    } elseif ( isset($_GET['del']) ) {

        $uid = Html::clean($_GET['del']);

        if ( (isset($_GET['modif'])) && ($_GET['modif'] == 'yes')) {

            $ldapconn = Ldap::lda_connect(LDAP_ADMIN_DN,LDAP_ADMIN_PASS);

            $filter = "(memberUid=$uid)";
            $attr = array("cn");

            $sr=ldap_search($ldapconn, $group_dn, $filter, $attr);
            $result = ldap_get_entries($ldapconn, $sr);
            $arraycn = array();

            print "<center>";

            print "<p>Suppression $uid en cours...</p>";

            // Verify if person exists...
            // TODO : /!\ il faudrait verifier le DN plutot que le uid
            if (!Ldap::is_uid($uid)) {
                print "<p class='error'>Erreur, compte inexistant</p>";
                EvoLog::log("Delete $uid failed (user doesn't exist).");
            // *Try* to verify if user is always in aliases...
            } elseif (Ldap::is_what($uid,'maildrop')>1) {
                print "<p class='error'>Erreur, compte encore pr&eacute;sent dans certains alias</p>";
                EvoLog::log("Delete $uid failed (user always in aliases).");
            // LDAP deletion
            } elseif (Ldap::lda_del($ldapconn,"uid=" .$uid. "," .$rdn)) {

                if (!$conf['domaines']['ldap']['virtual']) {

                    if($result["count"] > 0) {
                        for ($i=0; $i < $result["count"] ; $i++)
                        {
                            $arraycn[] = $result[$i]["cn"][0];
                        }
                        foreach($arraycn as $nameGroupe){
                            $remove_groupe["memberUid"] = $uid;
                            $rmGroupe = ldap_mod_del($ldapconn, "cn=".$nameGroupe.",".$group_dn, $remove_groupe);
                        }

                    }

                    // script suppression systeme
                    unix_del($uid);
                }

                // TODO : suppression params HORDE
                // $query = 'delete from horde_prefs where pref_uid="' .$uid. '"';

                print "<p class='strong'>Suppression $uid effectu&eacute;e.</p>";

                EvoLog::log("Del user ".$uid);

            } else {
                print "<p class='error>Erreur, suppression non effectu&eacute;e.</p>";
                EvoLog::log("Delete $uid failed");
            }

            print "</center>";

        } else {
            print "<center>";
            print "<p>Vous allez effacer compl&egrave;tement l'utilisateur <b>$uid</b><br />";
            print "Tous ses messages et param&egrave;tres seront d&eacute;finitivement perdus.</p>";
            print "<a href='compte.php?del=$uid&modif=yes'>Confirmer la suppression</a>";
            print "</center>";
        }

    // Ajouter un compte
    } else {

        /**
         * Account creation
         */

        /**
         * Set account creation
         */
        if ( (isset($_GET['modif'])) && ($_GET['modif'] == 'yes')) {

            //	Verification coherence des mots de passe
            if ( $_POST['pass1'] != $_POST['pass2'] ) {
                print "<p class='error>Erreur, vous avez tape deux mots de passe differents</p>";
                exit(1);
            }

            $postuid = Html::clean($_POST['uid']);

            if ( Auth::badpassword($_POST['pass1']) ) {
                print "<p class='error'>Erreur, mot de passe invalide
                    (trop court ou avec des caracteres incorrects)</p>";
                EvoLog::log("Set password failed for $postuid by $login");
                exit(1);
            }

            $cn = Html::justclean(Html::purgeaccents(utf8_decode($_POST['cn'])));

            if (badname($postuid)) {
                print "<p class='error>Erreur, <u>$postuid</u> est invalide.";
                print "Vous devez avoir entre 2 et 30 caracteres minuscules, chiffres ou";
                print " caracteres speciaux (tiret, point ou underscore).</p>";
                EvoLog::log("Add $postuid failed (bad name).");
                exit(1);
            }

            // Compatibilite anciens schemas LDAP
            //if (!$conf['evoadmin']['version'] == 1) {
                // mail and cn are auto-generated...
            $mail = $postuid. "@" .$_SESSION['domain'];
            $sn = $cn;
            //} else {
            //    $mail = Html::clean($_POST['mail']);
            //    $cn = Html::clean($_POST['cn']);
            //}

            // On verifie que le compte n'est pas deja pris...
            if (!$conf['domaines']['ldap']['virtual']) {
                if (Ldap::is_what($mail,"mail")) {
                    print "<p class='error'>Erreur, mail deja present !</p>";
                    EvoLog::log("$mail already exists by $login");
                    exit(1);
                }
                // ...sinon on le change legerement !
                $tmp = 1;
                $uid = $postuid;
                while (Ldap::is_uid($uid)) {
                    $tmp++;
                    $uid = $postuid.$tmp;
                }
            } else {
                $uid = $mail;
                if (Ldap::is_uid($uid)) {
                    print "<p class='error'>Erreur, mail deja present !</p>";
                    EvoLog::log("$uid already exists by $login");
                    exit(1);
                }
            }

            // Cas d'un compte Samba
            if (($conf['admin']['what'] == 2) || ($conf['admin']['what'] == 3)) {

                $ldapconn = Ldap::lda_connect(LDAP_ADMIN_DN,LDAP_ADMIN_PASS);

                $smbgroup = Html::clean($_POST['smbgroup']);
                $tmp = getsambagroups('unix');
                $gid = $tmp[$smbgroup];

                if(isset($_POST['smbgroupsecondaire']) && !empty($_POST['smbgroupsecondaire'])){
                    $arrayGroupes = $_POST['smbgroupsecondaire'];
                    foreach($arrayGroupes as $nameGroupe){
                        $entry_groupe["memberUid"] = $uid;
                        ldap_mod_add($ldapconn, "cn=".$nameGroupe.",".$group_dn, $entry_groupe);
                    }
                }
            } else {

                $gid = getgid($_SESSION['domain']);
            }

            if ( $gid < 1 ) {
               print "Erreur, groupe non detecte...";
               exit(1);
            }

            print "<center>";
            print "Ajout en cours...";

            // TODO : generer un UID different en LDAP non-virtual !!!
            $info["uid"]=$uid;
            // recuperer un uid number valide
            // TODO : erreur si uid non compris entre 1000 et 29999
            if ( $conf['domaines']['ldap']['virtual'] ) {
                $info["uidNumber"]= $conf['unix']['uid'];
            }
            else {
                $info["uidNumber"]= getfreeuid();
            }
            $info["gidNumber"]= $gid;
            $info["objectclass"][0] = "posixAccount";

            if (!$conf['domaines']['ldap']['virtual']) {
                $info["objectclass"][1] = "shadowAccount";
                $info["objectclass"][2] = "inetorgperson";

                // Choose what objects you want...
                if (($conf['admin']['what'] == 1) || ($conf['admin']['what'] == 3)) {
                    $info["objectclass"][3] = "mailAccount";
                } elseif ($conf['admin']['what'] == 2) {
                    $info["objectclass"][3] = "sambaSamAccount";
                }
                if ($conf['admin']['what'] == 3) {
                    $info["objectclass"][4] = "sambaSamAccount";
                }

            } else {
                $info["objectclass"][1] = "organizationalRole";
                $info["objectclass"][2] = "mailAccount";
            }

           // Compatibilite anciens schemas LDAP
           if ($conf['evoadmin']['version'] == 1) {
               $info["objectclass"][4] = "mailAlias";
               //$info["onlyAlias"] = "FALSE";
               $info["spamassassin"][0] = "whitelist_from dupont@seulement-cet-expediteur.com";
               $info["spamassassin"][1] = "whitelist_from *@tous-les-mails-de-ce-domaine.com";
           }

           $info["isActive"] = (isset($_POST['isactive'])) ? "TRUE" : "FALSE";
           $info["isAdmin"] = (isset($_POST['isadmin'])) ? "TRUE" : "FALSE";

           $info["cn"] = $cn;
           if (!$conf['domaines']['ldap']['virtual']) {
               $info["loginShell"] = Html::clean($_POST['loginshell']);
               $info["sn"] = $sn;
               $info["homeDirectory"] = "/home/" .$uid;

               // TODO: rajouter un isset pour verifier la presence de ce champ optionnel
               if ( $mail != '') {
                   $info["mail"] = $mail;
               }
            } else {
               $info["homeDirectory"] = "/home/vmail/" .$_SESSION['domain']. "/" .$postuid. "/";
            }

           // Cas d'un compte mail
           if (($conf['admin']['what'] == 1) || ($conf['admin']['what'] == 3)) {

               // Aliases
               $aliases = $_POST['alias'];

               // Compatibilite anciens schemas LDAP et mode "virtuel"
               if (($conf['evoadmin']['version'] == 1) || ($conf['domaines']['ldap']['virtual'])) {
                   // add @domain for each element
                   array_walk($aliases,'adddomain');
                }


               if (!$conf['domaines']['onlyone']) {
                   array_push($aliases,$postuid."@".$_SESSION['domain']);
               } else {
                   array_push($aliases,$uid);
               }

               // TODO: if ($conf['domaines']['onlyone'] != true) {
               //   verifier que le domaine des aliases est correct !!

               // on vire les valeurs nulles
               sort($aliases);
               while ( $aliases[0] == NULL ) {
               array_shift($aliases);
               }

               $info["mailacceptinggeneralid"] = $aliases;
               // tmartin 26/11/2009 : on ajoute un maildrop dans tous les cas
               //if (!$conf['domaines']['ldap']['virtual']) {
                   $info["maildrop"] = $uid;
               //}

               $info["accountActive"] = (isset($_POST['accountactive'])) ? "TRUE" : "FALSE";
               $info["courierActive"] = (isset($_POST['courieractive'])) ? "TRUE" : "FALSE";
               $info["webmailActive"] = (isset($_POST['webmailactive'])) ? "TRUE" : "FALSE";
               $info["authsmtpActive"] = (isset($_POST['authsmtpactive'])) ? "TRUE" : "FALSE";

           }

           // Cas d'un compte Samba
           if (($conf['admin']['what'] == 2) || ($conf['admin']['what'] == 3)) {

               $userRid = 2 * $info["uidNumber"] + 1000;

               $info["sambaSID"] = $conf['samba']['sid'].'-'.$userRid;
               if(!empty($_POST['displayname'])) {
                   $info["displayName"] = Html::clean($_POST['displayname']);
	       }
               $info["smbActive"] = (isset($_POST['smbactive'])) ? "TRUE" : "FALSE";
               $info["sambaDomainName"] =  $conf['samba']['dn'];

	       $tmp = getsambagroups('smb');
               $info["sambaPrimaryGroupSID"] = $conf['samba']['sid'] . $tmp[$smbgroup];
               $info["sambaPwdLastSet"] = strtotime("now");
               $info["sambaLMPassword"] = Ldap::sambalm($_POST['pass1']);
               $info["sambaNTPassword"] = Ldap::sambant($_POST['pass1']);
               $info["shadowLastChange"] = floor(strtotime("now")/(3600*24));

              $info["sambaPwdCanChange"] = "-2";
              $info["sambaPwdMustChange"] = "2147483647";
              $info["sambaKickoffTime"] = "2147483647";
              $info["sambaAcctFlags"] = "[XU         ]";

              $info["shadowExpire"] = "-1";
              $info["shadowInactive"] = "-1";
              $info["shadowMax"] = "200";
              $info["shadowMin"] = "0";
              $info["shadowWarning"] = "30";
              $info["shadowFlag"] = "-1";

           }

           $info["userPassword"] = "{SSHA}" .Ldap::ssha($_POST['pass1']);

           $ldapconn = Ldap::lda_connect(LDAP_ADMIN_DN,LDAP_ADMIN_PASS);
           // We use uid attribute for DN
           $sr=ldap_add($ldapconn,"uid=" .$uid. "," .$rdn, $info);

           // test if ldap connection is successful
           if ( $sr ) {

               if (!$conf['domaines']['ldap']['virtual']) {
                   // script creation systeme
                   unix_add($uid,getgid($_SESSION['domain']));
               } else {
                   mail($uid, 'Premier message',"Mail d'initialisation du compte.");
               }

               print "<p class='strong'>Ajout effectu&eacute;.</p>";
               print "<a href='compte.php?view=$uid'>Voir le compte cr&eacute;&eacute;</a>";
               EvoLog::log("Add user ".$uid);

               // notification par mail
               mailnotify($info,$_SESSION['domain'],$_POST['pass1']);

               if ($conf['samba']['admin_default'] == true) {
                    // ajout dans le groupe smbadmins par defaut #7015
                    $entry_group_smbadmins["memberUid"] = $uid;
                    ldap_mod_add($ldapconn, "cn=smbadmins,".$group_dn, $entry_group_smbadmins);
              }

           } else {
               print "<p class='error'>Erreur, envoyez le message d'erreur
                   suivant &agrave; votre administrateur :</p>";
               var_dump($info);
               EvoLog::log("Add $uid failed");
        }

        print "</center>";

        } else {
            ?>
                <center>

                <h4>Ajout d'un compte</h4>

            <form name="add"
                action="compte.php?modif=yes"
                method="post">

            <p class="italic">Remplissez lez champs, ceux contenant [*] sont obligatoires.</p>

            <table>

            <tr><td align="right">Login [*] :</td>
            <td align="left"><input type="text" name="uid" tabindex='1' />
            <?php
                if (!$conf['domaines']['onlyone']) {
                   print "@" .$_SESSION['domain'];
                }
            ?>
            </td></tr>

            <?php
                // Compatibilite anciens schemas LDAP ou mode "virtuel"
                if (($conf['evoadmin']['version'] != 1) && (!$conf['domaines']['ldap']['virtual'])) {
            ?>

            <tr><td align="right">Nom [*] :</td>
            <td align="left"><input type='text' name='sn' tabindex='2' /></td></tr>

            <?php
                }
            ?>

            <tr><td align="right">Nom Complet [*] :</td>
            <td align="left"><input type='text' name='cn' tabindex='3' /></td></tr>


            <tr><td align="right">Mot de passe [*] :</td>
            <td align="left"><input type="password" name="pass1" tabindex='4' /></td></tr>

            <tr><td align="right">Confirmation du mot de passe [*] :</td>
            <td align="left"><input type="password" name="pass2" tabindex='5' /></td></tr>

            <?php
                // Compatibilite anciens schemas LDAP
                if (!$conf['evoadmin']['version'] == 1) {
            ?>
            <tr><td align="right">Mail annonc&eacute; dans l'annuaire :</td>
            <td align="left"><input type='text' name='mail' /tabindex='6' ></td></tr>

            <?php
                }

                // only for samba mode
                if (($conf['admin']['what'] == 2) || ($conf['admin']['what'] == 3)) {
            ?>

            <tr><td colspan="2">
            <p class="italic">Gestion des parametres Samba</p>
            </td></tr>

            <tr><td align="right">Nom dans Samba :</td>
            <td align="left"><input type='text' name='displayname' tabindex='10' /></td></tr>

            <tr><td align="right">Groupe Samba primaire :</td>
            <td align="left"><select name="smbgroup">

            <?php
                $sambagroups = getsambagroups('smb');
                if(count($sambagroups) != 1) {
                    print '<option value="" disabled selected>Choisir un groupe</option>';
                }
                foreach ($sambagroups as $key=>$value) {
                    print "<option value='" . $key . "'";
                    if($key == $_SESSION['domain']) {
                        print ' selected="selected"';
                    }
                    print "> $key </option>\n";
                }

            ?>

            </select>

            <tr><td align="right">Groupe Samba secondaire :</td>
            <td align="left"><select style="margin-top:5px;" name="smbgroupsecondaire[]" multiple size=6>

                <?php
                $sambagroups = getsambagroups('smb');
                foreach ($sambagroups as $key=>$value) {
                        print "<option value='" . $key . "'";
                        if($key == $_SESSION['domain']) {
                            print ' selected="selected"';
                        }
                        print "> $key </option>\n";
                }
                ?>

                </select>


            <tr>
                <td align="right">Shell :</td>
                <td align="left">
                    <input type="text" name="loginshell" value="/bin/bash" />
                </td>
            </tr>

            <?php
            }

            // only for mail mode
            if (($conf['admin']['what'] == 1) || ($conf['admin']['what'] == 3)) {
            ?>

            <tr><td colspan="2">
            <p class="italic">En plus du mail utilisant le login, vous pouvez ajouter des alias.</p>
            </td></tr>

            <tr><td align="right">Alias :</td>
            <td align="left"><input type='text' name='alias[0]' tabindex='7' />
            <?php
                if (!$conf['domaines']['onlyone']) {
                   print "@" .$_SESSION['domain'];
                }
            ?>
            </td></tr>

            <tr><td align="right">Alias :</td>
            <td align="left"><input type='text' name='alias[1]' tabindex='8' />
             <?php
                if (!$conf['domaines']['onlyone']) {
                   print "@" .$_SESSION['domain'];
                }
            ?>
            </td></tr>

            <tr><td align="right">Alias :</td>
            <td align="left"><input type='text' name='alias[2]' tabindex='9' />
            <?php
                if (!$conf['domaines']['onlyone']) {
                   print "@" .$_SESSION['domain'];
                }
            ?>
            </td></tr>

            <?php
                }
            ?>

            <tr><td colspan="2">
            <p class="italic">Cochez les cases pour choisir les autorisations du compte.</p>
            </td></tr>

            <tr><td align="right">Activation globale :</td>
            <td align="left"><input type='checkbox' tabindex='11'
                name='isactive' checked /></td></tr>

            <tr><td align="right">Compte admin :</td>
            <td align="left"><input type='checkbox' tabindex='14'
                name='isadmin' /></td></tr>

            <?php // only for samba mode
                if (($conf['admin']['what'] == 2) || ($conf['admin']['what'] == 3)) {
            ?>

            <tr><td align="right">Compte Samba actif :</td>
            <td align="left"><input type='checkbox' tabindex='13'
                name='smbactive' checked /></td></tr>

            <?php
                }
                // only for mail mode
                if (($conf['admin']['what'] == 1) || ($conf['admin']['what'] == 3)) {
            ?>

            <tr><td align="right">Compte mail actif :</td>
            <td align="left"><input type='checkbox' tabindex='12'
                name='accountactive' checked /></td></tr>

            <tr><td align="right">Utilisation POP/IMAP :</td>
            <td align="left"><input type='checkbox' tabindex='15'
                name='courieractive' checked /></td></tr>

            <tr><td align="right">Webmail actif :</td>
            <td align="left"><input type='checkbox' tabindex='16'
                name='webmailactive' checked /></td></tr>

            <tr><td align="right">Authentification SMTP :</td>
            <td align="left"><input type='checkbox' tabindex='17'
                name='authsmtpactive' <?php if ($conf['evoadmin']['useauthsmtp']) print "checked" ?> /></td></tr>

            <?php
                }
            ?>

            <tr><td>&nbsp;</td><td align="left">
            <p><input type="submit" class="button" tabindex='18'
                value="Valider" name="valider" onclick='return submit_add();'  /></p>
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

include EVOADMIN_BASE . 'fin.php';

?>

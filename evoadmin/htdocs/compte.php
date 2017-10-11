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
    include EVOADMIN_BASE . 'debut.php';

    $rdn = $_SESSION['rdn'];

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

#                $postamavisBypassSpamChecks = (isset($_POST['amavisBypassSpamChecks']) ? 'TRUE' : 'FALSE');
#                if ( $info[0]["amavisBypassSpamChecks"][0] != $postamavisBypassSpamChecks ) {
#                    $new["amavisBypassSpamChecks"] = $postamavisBypassSpamChecks;
#                }

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
            if ( (isset($new)) && ($new != NULL) ) {
                $sr=ldap_modify($ldapconn,"uid=" .$uid. ",".$rdn,$new);

                // Si LDAP est content, c'est bon :)
                if ( $sr ) {
                    print "<p class='strong'>Modifications effectu&eacute;es.</p>";
                    print "<a href='compte.php?view=$uid'>Voir le compte modifi&eacute;</a>";
                } else {
                    print "<div class=\"alert alert-warning\" role=\"alert\">Erreur, envoyez le message d'erreur suivant &agrave; votre administrateur :</div>";
                    var_dump($new);
                    Evolog::log("Modify error of $uid by $login");
                }

            } else {
                print "<div class=\"alert alert-info\" role=\"alert\">Aucune modification n&eacute;cessaire.</div>";
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

            print "<div class='container'>";
            print "<h2>Modification du compte $uid</h2><hr>";

            print"<div class=\"alert alert-info\" role=\"alert\">Modifiez les champs que vous d&eacute;sirez changer.<br /> [*] indique ceux qui ne doivent pas &ecirc;tre nuls.<br />Vous pouvez r&eacute;initialiser le mot de passe si besoin.</div>";

            print "<form name='add' action='compte.php?view=$uid&modif=yes' method='post' class='form-horizontal'>";

			// Compatibilite anciens schemas LDAP ou mode "virtuel"
            if (($conf['evoadmin']['version'] != 1) && (!$conf['domaines']['ldap']['virtual'])) {
                print "<div class='form-group'>";
				print "<label for='sn' class='col-sm-3 control-label'>Nom [*] :</label>";
				print "<div class='col-sm-7'><input type='text' name='sn' class='form-control' value='$sn' /></div>";
				print "<div class='col-sm-2 control-label'></div>";
				print "</div>";
            }
            
            print "<div class='form-group'>";
			print "<label for='cn' class='col-sm-3 control-label'>Nom Complet [*] :</label>";
			print "<div class='col-sm-7'><input type='text' name='cn' class='form-control' value='$cn' /></div>";
			print "<div class='col-sm-2 control-label'></div>";
			print "</div>";

            print "<div class='form-group'>";
			print "<label for='pass1' class='col-sm-3 control-label'>Nouveau mot de passe :</label>";
			print "<div class='col-sm-7'><input type='password' name='pass1' class='form-control' /></div>";
			print "<div class='col-sm-2 control-label'></div>";
			print "</div>";

            print "<div class='form-group'>";
			print "<label for='pass2' class='col-sm-3 control-label'>Confirmation du mot de passe :</label>";
			print "<div class='col-sm-7'><input type='password' name='pass2' class='form-control' /></div>";
			print "<div class='col-sm-2 control-label'></div>";
			print "</div>";


            // Compatibilite anciens schemas LDAP
            if ($conf['evoadmin']['version'] == 1) {
	            print "<div class='form-group'>";
				print "<label for='mail' class='col-sm-3 control-label'>Mail principal : </label>";
				print "<div class='col-sm-7'>$mail<input type='hidden' name='mail' value='$mail' class='form-control' /></div>";
				print "<div class='col-sm-2 control-label'></div>";
				print "</div>";
            } elseif (!$conf['domaines']['ldap']['virtual']) {
	            print "<div class='form-group'>";
				print "<label for='mail' class='col-sm-3 control-label'>Mail annonc&eacute; dans l'annuaire : </label>";
				print "<div class='col-sm-7'><input type='text' name='mail' value='$mail' class='form-control' /></div>";
				print "<div class='col-sm-2 control-label'></div>";
				print "</div>";
            }

            // only for samba mode
            if (($conf['admin']['what'] == 2) || ($conf['admin']['what'] == 3)) {

	            print "<hr><h5>Modification pour Samba</h5>";
	
	            print "<div class='form-group'>";
				print "<label for='displayname' class='col-sm-3 control-label'>Nom dans Samba : </label>";
				print "<div class='col-sm-7'><input type='text' name='displayname' value='$displayname' class='form-control' /></div>";
				print "<div class='col-sm-2 control-label'></div>";
				print "</div>";
	                
	            print "<div class='form-group'>";
				print "<label for='loginshell' class='col-sm-3 control-label'>Shell : </label>";
				print "<div class='col-sm-7'><input type='text' name='loginshell' value='".$info[0]['loginshell'][0]."' class='form-control' /></div>";
				print "<div class='col-sm-2 control-label'></div>";
				print "</div>";
				
	            print "<div class='form-group'>";
				print "<label for='loginshell' class='col-sm-3 control-label'>Shell : </label>";
				print "<div class='col-sm-7'><input type='text' name='loginshell' value='".$info[0]['loginshell'][0]."' class='form-control' /></div>";
				print "<div class='col-sm-2 control-label'></div>";
				print "</div>";
				
				print "<hr><h5>Groupe Samba : $sambagroup</h5>";
			}
			
			 // only for mail mode
            if (($conf['admin']['what'] == 1) || ($conf['admin']['what'] == 3)) {

                print "<div class='well'>Ajoutez/modifiez/supprimez les alias (mails accept&eacute;s en entr&eacute;e).<br />Un minimum d'un alias est requis. M&ecirc;mes instructions<br />pour les redirections (compte(s) dans le(s)quel(s) est/sont d&eacute;livr&eacute;(s) les mails).</div>";

                for ($i=0;$i<$info[0]["mailacceptinggeneralid"]['count'];$i++) {
                    
                    if (!$conf['domaines']['onlyone']) {
                        $info[0]['mailacceptinggeneralid'][$i] =
                            preg_replace("/@".$_SESSION['domain']."/",'',$info[0]['mailacceptinggeneralid'][$i]);
                    }

		            print "<div class='form-group'>";
					print "<label for='mailaccept[$i]' class='col-sm-3 control-label'>Mail accept&eacute; en entr&eacute;e : </label>";
					print "<div class='col-sm-7'><input type='text' name='mailaccept[$i]' value='".$info[0]['mailacceptinggeneralid'][$i]."' class='form-control' /></div>";
					print "<div class='col-sm-2 control-label'>";
						if (!$conf['domaines']['onlyone']) {
	                        print "@" .$_SESSION['domain'];
	                    }
					print "</div>";
					print "</div>";

                }
                
            	print "<div class='form-group'>";
				print "<label for='mailaccept[$i]' class='col-sm-3 control-label'>Cr&eacute;ation d'un nouveau mail accept&eacute; en entr&eacute;e : </label>";
				print "<div class='col-sm-7'><input type='text' name='mailaccept[$i]' value='".$info[0]['mailacceptinggeneralid'][$i]."' class='form-control' /></div>";
				print "<div class='col-sm-2 control-label'>";
					if (!$conf['domaines']['onlyone']) {
                        print "@" .$_SESSION['domain'];
                    }
				print "</div>";
				print "</div>";
                


                for ($i=0;$i<$info[0]["maildrop"]['count'];$i++) {
	                print "<div class='form-group'>";
					print "<label for='maildrop[$i]' class='col-sm-3 control-label'>Mails entrants redirig&eacute;s vers : </label>";
					print "<div class='col-sm-7'><input type='text' name='maildrop[$i]' value='".$info[0]['maildrop'][$i]."' class='form-control' /></div>";
					print "<div class='col-sm-2 control-label'>";
						if (!$conf['domaines']['onlyone']) {
	                        print "@" .$_SESSION['domain'];
	                    }
					print "</div>";
					print "</div>";
                }

                print "<div class='form-group'>";
				print "<label for='maildrop[$i]' class='col-sm-3 control-label'>Nouvelle redirection vers : </label>";
				print "<div class='col-sm-7'><input type='text' name='maildrop[$i]' class='form-control' /></div>";
				print "<div class='col-sm-2 control-label'></div>";
				print "</div>";
            }
			
			            print "<hr><h5>Modifiez les autorisations du compte si besoin.</h5>";

            $isactive= ($info[0]["isactive"][0] == 'TRUE') ? 'checked' : '';
            print "<div class='form-group'>";
			print "<label for='isactive' class='col-sm-3 control-label'>Activation globale : </label>";
			print "<div class='col-sm-7'><input type='checkbox' name='isactive' $isactive class='form-control move-left' /></div>";
			print "<div class='col-sm-2 control-label'></div>";
			print "</div>";

            $isadmin= ($info[0]["isadmin"][0] == 'TRUE') ? 'checked' : '';
            print "<div class='form-group'>";
			print "<label for='isadmin' class='col-sm-3 control-label'>Compte admin : </label>";
			print "<div class='col-sm-7'><input type='checkbox' name='isadmin' $isadmin class='form-control move-left' /></div>";
			print "<div class='col-sm-2 control-label'></div>";
			print "</div>";

            // only for samba mode
            if (($conf['admin']['what'] == 2) || ($conf['admin']['what'] == 3)) {
                $smbactive= ($info[0]["smbactive"][0] == 'TRUE') ? 'checked' : '';
	            print "<div class='form-group'>";
				print "<label for='smbactive' class='col-sm-3 control-label'>Compte Samba actif : </label>";
				print "<div class='col-sm-7'><input type='checkbox' name='smbactive' $smbactive class='form-control move-left' /></div>";
				print "<div class='col-sm-2 control-label'></div>";
				print "</div>";

                $accountactive= ($info[0]["accountactive"][0] == 'TRUE') ? 'checked' : '';
	            print "<div class='form-group'>";
				print "<label for='accountactive' class='col-sm-3 control-label'>Compte mail actif : </label>";
				print "<div class='col-sm-7'><input type='checkbox' name='accountactive' $accountactive class='form-control move-left' /></div>";
				print "<div class='col-sm-2 control-label'></div>";
				print "</div>";

                $webmailactive= ($info[0]["webmailactive"][0] == 'TRUE') ? 'checked' : '';
	            print "<div class='form-group'>";
				print "<label for='webmailactive' class='col-sm-3 control-label'>Webmail actif : </label>";
				print "<div class='col-sm-7'><input type='checkbox' name='webmailactive' $webmailactive class='form-control move-left' /></div>";
				print "<div class='col-sm-2 control-label'></div>";
				print "</div>";

            }

            // only for mail mode
            if (($conf['admin']['what'] == 1) || ($conf['admin']['what'] == 3)) {

                $courieractive= ($info[0]["courieractive"][0] == 'TRUE') ? 'checked' : '';
	            print "<div class='form-group'>";
				print "<label for='courieractive' class='col-sm-3 control-label'>Utilisation POP/IMAP : </label>";
				print "<div class='col-sm-7'><input type='checkbox' name='courieractive' $courieractive class='form-control move-left' /></div>";
				print "<div class='col-sm-2 control-label'></div>";
				print "</div>";

                $authsmtpactive= ($info[0]["authsmtpactive"][0] == 'TRUE') ? 'checked' : '';
	            print "<div class='form-group'>";
				print "<label for='authsmtpactive' class='col-sm-3 control-label'>Authentification SMTP : </label>";
				print "<div class='col-sm-7'><input type='checkbox' name='authsmtpactive' $authsmtpactive class='form-control move-left' /></div>";
				print "<div class='col-sm-2 control-label'></div>";
				print "</div>";

#                $amavisBypassSpamChecks= ($info[0]["amavisbypassspamchecks"][0] == 'TRUE') ? 'checked' : '';
#	            print "<div class='form-group'>";
#				print "<label for='amavisBypassSpamChecks' class='col-sm-3 control-label'>Désactivation Antispam : </label>";
#				print "<div class='col-sm-7'><input type='checkbox' name='amavisBypassSpamChecks' $amavisBypassSpamChecks class='form-control move-left' /></div>";
#				print "<div class='col-sm-2 control-label'></div>";
#				print "</div>";

            }

            print "<div class='text-center'><button type='submit' class='btn btn-primary' onclick='return submit_add();'>Valider</button></div>";

            print '</form>';
            print '</div>';
        }

    /**
     * Delete account
     */
    } elseif ( isset($_GET['del']) ) {

        $uid = Html::clean($_GET['del']);
        
        if ( (isset($_GET['modif'])) && ($_GET['modif'] == 'yes')) {

            $ldapconn = Ldap::lda_connect(LDAP_ADMIN_DN,LDAP_ADMIN_PASS);

            print "<div class=\"alert alert-info\" role=\"alert\">";

            print "<p>Suppression $uid en cours...</p>";

            // Verify if person exists...
            // TODO : /!\ il faudrait verifier le DN plutot que le uid
            if (!Ldap::is_uid($uid)) {
                print "<p class='error>Erreur, compte inexistant</p>";
                EvoLog::log("Delete $uid failed (user doesn't exist).");
            // *Try* to verify if user is always in aliases...
            } elseif (Ldap::is_what($uid,'maildrop')>1) {
                print "<p class='error>Erreur, compte encore pr&eacute;sent dans certains alias</p>";
                EvoLog::log("Delete $uid failed (user always in aliases).");
            // LDAP deletion
            } elseif (Ldap::lda_del($ldapconn,"uid=" .$uid. "," .$rdn)) {
           
                if (!$conf['domaines']['ldap']['virtual']) {
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

            print "</div>";
        
        } else {
            print "<div class=\"alert alert-info\" role=\"alert\">"; 
            print "<p>Vous allez effacer compl&egrave;tement l'utilisateur <b>$uid</b><br />";
            print "Tous ses messages et param&egrave;tres seront d&eacute;finitivement perdus.</p>";
            print "<a href='compte.php?del=$uid&modif=yes'>Confirmer la suppression</a>";
            print "</div>"; 
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
                print "<div class=\"alert alert-danger\" role=\"alert\">Erreur, vous avez tape deux mots de passe differents</div>";
                exit(1);
            }

            $postuid = Html::clean($_POST['uid']);

            if ( Auth::badpassword($_POST['pass1']) ) {
                print "<div class=\"alert alert-danger\" role=\"alert\">Erreur, mot de passe invalide
                    (trop court ou avec des caracteres incorrects)</div>";
                EvoLog::log("Set password failed for $postuid by $login");
                exit(1);
            }

            $cn = Html::justclean(Html::purgeaccents(utf8_decode($_POST['cn']))); 

            if (badname($postuid)) {
                print "<div class=\"alert alert-danger\" role=\"alert\">Erreur, <u>$postuid</u> est invalide.";
                print "Vous devez avoir entre 2 et 30 caracteres minuscules, chiffres ou";
                print " caracteres speciaux (tiret, point ou underscore).</div>";
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
                    print "<div class=\"alert alert-danger\" role=\"alert\">Erreur, mail deja present !</div>";
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
                    print "<div class=\"alert alert-danger\" role=\"alert\">Erreur, mail deja present !</div>";
                    EvoLog::log("$uid already exists by $login");
                    exit(1);
                }
            }

            // Cas d'un compte Samba
            if (($conf['admin']['what'] == 2) || ($conf['admin']['what'] == 3)) {
            
                $smbgroup = Html::clean($_POST['smbgroup']);
                $tmp = getsambagroups('unix');
                $gid = $tmp[$smbgroup];
            } else {

                $gid = getgid($_SESSION['domain']);
            }

            if ( $gid < 1 ) {
               print "Erreur, groupe non detecte...";
               exit(1);
            }

            print "<div class='container'>";
            print "<div class=\"alert alert-info\" role=\"alert\">Ajout en cours...</div>";
          
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
#                $info["objectclass"][3] = "amavisAccount";
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
#               $info["amavisBypassSpamChecks"] = (isset($_POST['amavisBypassSpamChecks'])) ? "TRUE" : "FALSE";

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

               print "<div class=\"alert alert-succes\" role=\"alert\">Ajout effectu&eacute;.</div>";
               print "<a href='compte.php?view=$uid'><button class='btn btn-primary'>Voir le compte cr&eacute;&eacute;</button></a>";
               EvoLog::log("Add user ".$uid);

               // notification par mail
               mailnotify($info,$_SESSION['domain'],$_POST['pass1']); 

           } else {
               print "<div class=\"alert alert-danger\" role=\"alert\">Erreur, envoyez le message d'erreur suivant &agrave; votre administrateur :</div>";
               var_dump($info);
               EvoLog::log("Add $uid failed");
        }

        print "</div>";

        } else {
            ?>
                <div class="container">
                
                <h2>Ajout d'un compte</h2><hr>

            <form name="add" action="compte.php?modif=yes" method="post" class="form-horizontal">
	        <div class="alert alert-info" role="alert">Remplissez lez champs, ceux contenant [*] sont obligatoires.</div>


			<div class="form-group">
				<label for="uid" class="col-sm-3 control-label">Login [*] :</label>
				<div class="col-sm-7"><input type="text" name="uid" class="form-control" /></div>
				<div class="col-sm-2 control-label"><?php if (!$conf['domaines']['onlyone']) { print "@" .$_SESSION['domain']; } ?></div>
			</div>


            <?php
                // Compatibilite anciens schemas LDAP ou mode "virtuel"
                if (($conf['evoadmin']['version'] != 1) && (!$conf['domaines']['ldap']['virtual'])) {
            ?>

            <div class="form-group">
				<label for="sn"     class="col-sm-3 control-label">Nom [*] :</label>
				<div class="col-sm-7"><input type="text" name="sn" class="form-control" /></div>
				<div class="col-sm-2 control-label"></div>
			</div>
			
            <?php
                }
            ?>


			<div class="form-group">
				<label for="cn"     class="col-sm-3 control-label">Nom Complet [*] :</label>
				<div class="col-sm-7"><input type="text" name="cn" class="form-control" /></div>
				<div class="col-sm-2 control-label"></div>
			</div>

			<div class="form-group">
				<label for="pass1"     class="col-sm-3 control-label">Mot de passe [*] :</label>
				<div class="col-sm-7"><input type="password" name="pass1" class="form-control" /></div>
				<div class="col-sm-2 control-label"></div>
			</div>

			<div class="form-group">
				<label for="pass2"     class="col-sm-3 control-label">Confirmation du mot de passe [*] :</label>
				<div class="col-sm-7"><input type="password" name="pass2" class="form-control" /></div>
				<div class="col-sm-2 control-label"></div>
			</div>

            <?php
                // Compatibilite anciens schemas LDAP
                if (!$conf['evoadmin']['version'] == 1) {
            ?>
			<div class="form-group">
				<label for="mail"     class="col-sm-3 control-label">Mail annonc&eacute; dans l'annuaire :</label>
				<div class="col-sm-7"><input type="text" name="mail" class="form-control" /></div>
				<div class="col-sm-2 control-label"></div>
			</div>

            <?php
                }

                // only for samba mode
                if (($conf['admin']['what'] == 2) || ($conf['admin']['what'] == 3)) {
            ?>

            <hr><h5>Gestion des parametres Samba</h5>

			<div class="form-group">
				<label for="displayname"     class="col-sm-3 control-label">Nom dans Samba :</label>
				<div class="col-sm-7"><input type="text" name="displayname" class="form-control" /></div>
				<div class="col-sm-2 control-label"></div>
			</div>

			<div class="form-group">
				<label for="smbgroup"     class="col-sm-3 control-label">Groupe Samba :</label>
				<div class="col-sm-7">
					<select name="smbgroup">
						<option value="" disabled selected>Choisir un groupe</option>
						<?php
				        	foreach (getsambagroups('smb') as $key=>$value) {
				            	print "<option value='" . $key . "'> $key </option>\n";
				            }
				        ?>
					</select>
				</div>
				<div class="col-sm-2 control-label"></div>
			</div>

  			<div class="form-group">
				<label for="loginshell"     class="col-sm-3 control-label">Shell :</label>
				<div class="col-sm-7"><input type="text" name="loginshell" class="form-control" value="/bin/bash" /></div>
				<div class="col-sm-2 control-label"></div>
			</div>
            
            <?php
            }

            // only for mail mode
            if (($conf['admin']['what'] == 1) || ($conf['admin']['what'] == 3)) {
            ?>

			<hr><h5>En plus du mail utilisant le login, vous pouvez ajouter des alias.</h5>

  			<div class="form-group">
				<label for="alias"     class="col-sm-3 control-label">Alias :</label>
				<div class="col-sm-7"><input type="text" name="alias[0]" class="form-control" /></div>
				<div class="col-sm-2 control-label"><?php if (!$conf['domaines']['onlyone']) { print "@" .$_SESSION['domain']; } ?></div>
			</div>

  			<div class="form-group">
				<label for="alias[1]"     class="col-sm-3 control-label">Alias :</label>
				<div class="col-sm-7"><input type="text" name="alias[1]" class="form-control" /></div>
				<div class="col-sm-2 control-label"><?php if (!$conf['domaines']['onlyone']) { print "@" .$_SESSION['domain']; } ?></div>
			</div>
			
			<div class="form-group">
				<label for="alias[2]"     class="col-sm-3 control-label">Alias :</label>
				<div class="col-sm-7"><input type="text" name="alias[2]" class="form-control" /></div>
				<div class="col-sm-2 control-label"><?php if (!$conf['domaines']['onlyone']) { print "@" .$_SESSION['domain']; } ?></div>
			</div>

            <?php
                }
            ?>

            <hr><h5>Cochez les cases pour choisir les autorisations du compte.</h5>

			<div class="form-group">
				<label for="isactive"     class="col-sm-3 control-label">Alias :</label>
				<div class="col-sm-7"><input type='checkbox' name='isactive' checked class="form-control move-left" /></div>
				<div class="col-sm-2 control-label"></div>
			</div>

			<div class="form-group">
				<label for="isadmin"     class="col-sm-3 control-label">Compte admin :</label>
				<div class="col-sm-7"><input type='checkbox' name='isadmin' checked class="form-control move-left" /></div>
				<div class="col-sm-2 control-label"></div>
			</div>



            <?php // only for samba mode
                if (($conf['admin']['what'] == 2) || ($conf['admin']['what'] == 3)) {
            ?>

			<div class="form-group">
				<label for="smbactive"     class="col-sm-3 control-label">Compte Samba actif :</label>
				<div class="col-sm-7"><input type='checkbox' name='smbactive' checked class="form-control move-left" /></div>
				<div class="col-sm-2 control-label"></div>
			</div>


            <?php 
                }
                // only for mail mode
                if (($conf['admin']['what'] == 1) || ($conf['admin']['what'] == 3)) {
            ?>
            
            <div class="form-group">
				<label for="courieractive"     class="col-sm-3 control-label">Utilisation POP/IMAP :</label>
				<div class="col-sm-7"><input type='checkbox' name='courieractive' checked class="form-control move-left" /></div>
				<div class="col-sm-2 control-label"></div>
			</div>

            <div class="form-group">
				<label for="authsmtpactive"     class="col-sm-3 control-label">Authentification SMTP :</label>
				<div class="col-sm-7"><input type='checkbox' name='authsmtpactive' <?php if ($conf['evoadmin']['useauthsmtp']) print "checked" ?> class="form-control move-left" /></div>
				<div     class="col-sm-3 control-label"></div>
			</div>

<!--
            <div class="form-group">
				<label for="amavisBypassSpamChecks"     class="col-sm-3 control-label">Désactivation Antispam :</label>
				<div class="col-sm-7"><input type='checkbox' name='amavisBypassSpamChecks' <?php if ($conf['evoadmin']['amavisBypassSpamChecks']) print "checked" ?> class="form-control move-left" /></div>
				<div class="col-sm-2 control-label"></div>
			</div>
-->

            <?php
                }
            ?>
			<div class="text-center"><button type="submit" class="btn btn-primary" onclick='return submit_add();'>valider</button></div>

            </form>

            </div>

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

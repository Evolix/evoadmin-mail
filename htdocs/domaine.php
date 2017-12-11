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

// Force authentication on this page
require_once("lib/auth.php");

/**
 * Path
 */
define('EVOADMIN_BASE','./');

    /**
     * Requires
     */
    require_once EVOADMIN_BASE . 'lib/common.php';

    include EVOADMIN_BASE . 'inc/haut.php';
    include EVOADMIN_BASE . 'inc/debut.php';

    if ( (!superadmin($login)) || ($conf['domaines']['driver'] != 'ldap') ) {

	print "<div class=\"alert alert-danger\" role=\"alert\">Vous n'avez pas les droits pour cette page</div>";
	EvoLog::log("Access denied on domaine.php");

	include EVOADMIN_BASE . 'inc/fin.php';
	exit(1);
    }

    // Supprimer un domaine
    if ( isset($_GET['del']) ) {

        $domain = Html::clean($_GET['del']);
        
        if ( (isset($_GET['modif'])) && ($_GET['modif'] == 'yes')) {
            print "<div class=\"alert alert-warning\" role=\"alert\">Votre demande a été envoyé au support. <br> Concernant le domaine <b>$domain</b>...</div>";

            // Envoit d'une demande de suppression
    	$entete   = "From: ".$conf['admin']['mail']."\n";
	    $entete  .= "MIME-Version: 1.0\n";
	    $entete  .= "Content-type: text/plain; charset=utf-8\n";
	    $entete  .= "Content-Transfer-Encoding: quoted-printable\n";            
      
	    $contenu  = "Bonjour,\n\n";
	    $contenu .= "Pourriez vous supprimer le domaine : $domain\n";
	    $contenu .= "Cordialement,\n";
            
            mail($conf['admin']['mail'], 'Suppression d\'un domaine mail',$contenu,$entete);
           
            // TODO : Verifier que l'objet existe avant sa suppression
            //$ldapconn = Ldap::lda_connect(LDAP_ADMIN_DN,LDAP_ADMIN_PASS);
            //$sr = Ldap::lda_del($ldapconn,"domain=" .$domain. "," .$rdn);

            if ( $sr ) {
                // script suppression systeme
                //unix_del_dom($domain);
                // TODO : suppression comptes associes
                print "<div class=\"alert alert-succes\" role=\"alert\">Suppression $domain effectu&eacute;e.</div>";
                EvoLog::log("Del domain ".$domain);

            } else {
                print "<div class=\"alert alert-danger\" role=\"alert\">Erreur, suppression non effectu&eacute;e.</div>";
                EvoLog::log("Delete $domain failed");
            }

        
        } else {
            print "<div class=\"alert alert-info\" role=\"alert\">Vous souhaitez effacer compl&egrave;tement le domaine <b>$domain</b>...<br /> Tous les messages et param&egrave;tres seront d&eacute;finitivement perdus.</p><a href='domaine.php?del=$domain&modif=yes'>Confirmer la suppression</a></div>"; 
            
        }

    } else {

        // Ajouter un domaine
        if ( (isset($_GET['modif'])) && ($_GET['modif'] == 'yes')) {
        
            $domain = Html::clean($_POST['domain']); 
			
			print "<div class='container'>";
            print "<div class=\"alert alert-warning\" role=\"alert\">Ajout en cours...</div>";
          
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
                        print "<div class=\"alert alert-succes\" role=\"alert\">Ajout effectu&eacute;.</div>";
                        EvoLog::log("Add domain ".$domain);

                        // notification par mail
                        domainnotify($domain); 

                    } else {
                        print "<div class=\"alert alert-danger\" role=\"alert\">Erreur, envoyez le message d'erreur suivant &agrave; votre administrateur :<pre>";
                        var_dump($info);
                        var_dump($info2);
                        EvoLog::log("Add $domain failed");
                        print "</pre></div>";
                    }
                } elseif ( $conf['evoadmin']['version'] == 3) {
                    // Version specifique pour steloi
                    $info["cn"] = $domain;
                    $info["objectclass"][0] = "postfixDomain";
                    $info["postfixTransport"] = "local:";
                    //$info["accountActive"] = (isset($_POST['isactive'])) ? "TRUE" : "FALSE";
                    // recuperer un uid number valide
                    // TODO : erreur si uid non compris entre 1000 et 29999
                    $info["gidNumber"]= getfreegid();

                    $info2["cn"] = $domain;
                    $info2["objectclass"][0]="posixGroup";
                    $info2["objectclass"][1]="sambaGroupMapping";
                    $info2["gidNumber"]= $info["gidNumber"];
                    $info2["displayName"]= $domain;
                    $info2["sambaGroupType"]= "2";
                    // generation du sambaSID comme dans le add.pl.
                    $info2["sambaSID"]= $conf['samba']['sid'] . (2*$info["gidNumber"]+1000);

                    $ldapconn = Ldap::lda_connect(LDAP_ADMIN_DN,LDAP_ADMIN_PASS);

                    // on teste si LDAP est content
                    if ( ldap_add($ldapconn,"cn=" .$domain. "," .LDAP_BASE, $info)
                        && ldap_add($ldapconn,"cn=" .$domain. ",ou=groups," .LDAP_BASE, $info2) ) {

                        // script ajout systeme (TODO : quota)
                        //unix_add($uid,getgid($_SESSION['domain']));
                        print "<div class=\"alert alert-success\" role=\"alert\">Ajout effectu&eacute;.</div>";
                        EvoLog::log("Add domain ".$domain);

                        // notification par mail
                        domainnotify($domain); 

                    } else {
                        print "<div class=\"alert alert-danger\" role=\"alert\">Erreur, envoyez le message d'erreur suivant &agrave; votre administrateur :<pre>";
                        var_dump($info);
                        var_dump($info2);
                        EvoLog::log("Add $domain failed");
                        print "</pre></div>";
                    }
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

                    domain_add($domain);

                    print "<div class=\"alert alert-success\" role=\"alert\">Ajout effectu&eacute;.</div>";
                    EvoLog::log("Add domain ".$domain);

                    // notification par mail
                    domainnotify($domain); 

                } else {
                    print "<div class=\"alert alert-danger\" role=\"alert\">Erreur, envoyez le message d'erreur suivant &agrave; votre administrateur :<pre>";
                    var_dump($info);
                    EvoLog::log("Add $domain failed");
					print "</pre></div>";

                }

            }
			print "</div>";

        // Formulaire d'ajout d'un domaine
        } else {
            ?>
                <div class="container">
                
                <h4>Ajout d'un domaine</h4>

            <form name="add" action="domaine.php?modif=yes" method="post" class="form-horizontal">
 
            <div class="alert alert-info" role="alert">Remplissez lez champs, ceux contenant [*] sont obligatoires.</div>

			<div class="form-group">
				<label for="domain" class="col-sm-3 control-label">Domaine [*] :</label>
				<div class="col-sm-9"><input type="text" name="domain" class="form-control" /></div>
			</div>

			<div class="form-group">
				<label for="isactive" class="col-sm-3 control-label">Activation globale :</label>
				<div class="col-sm-9"><input type='checkbox' name='isactive' checked  class="form-control move-left"/></div>
			</div>

			<div class="text-center"><button type="submit" class="btn btn-primary">Valider</button></div>

            </form>

	       </div>
	   
        <?php
        }
    }

include EVOADMIN_BASE . 'inc/fin.php';

?>

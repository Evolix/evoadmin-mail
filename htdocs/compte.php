<?php

// Load config and autoload class
require_once("lib/config.php");

// Force authentication on this page
require_once("lib/auth.php");

require_once('lib/common.php');

include('inc/haut.php');
include('inc/debut.php');

if (!empty($_POST['cn'])) {
    //  Verification coherence des mots de passe    
    if ( $_POST['pass1'] != $_POST['pass2'] ) {
        print "<div class=\"alert alert-danger\" role=\"alert\">Erreur, vous avez tape deux mots de passe differents</div>";
        exit(1);
    }

    print "<div class='container'>";

    $uid = (!empty($_GET['account'])) ? $account->getUid() : Html::clean($_POST['uid']);
    $cn = Html::justclean(Html::purgeaccents(utf8_decode($_POST['cn'])));
    $password = (!empty($_POST['pass1'])) ? $_POST['pass1'] : NULL; 
    $actif = (!empty($_POST['isactive'])) ? true : false;
    $admin = (!empty($_POST['isadmin'])) ? true : false;
    $courier = (!empty($_POST['courieractive'])) ? true : false;
    $authsmtp = (!empty($_POST['authsmtpactive'])) ? true : false;

    try {
        if (!empty($_GET['account'])) {
            print "<div class=\"alert alert-info\" role=\"alert\">Modification en cours...</div>";
            $account->update($cn,$password,$actif,$admin,$actif,$courier,$authsmtp);
            print "<div class=\"alert alert-succes\" role=\"alert\">Modification effectu&eacute;.</div>";
        } else {
            print "<div class=\"alert alert-info\" role=\"alert\">Ajout en cours...</div>";
            $domain->addAccount($uid,$cn,$password,$actif,$admin,$actif,$courier,$authsmtp);
            print "<div class=\"alert alert-succes\" role=\"alert\">Ajout effectu&eacute;.</div>";
            print '<a href="compte.php?domain='.$domain->getName().'&account='.$uid.'"><button class="btn btn-primary">Voir le compte cr&eacute;&eacute;</button></a>';
        }
    } catch (Exception $e) {
        print '<div class="alert alert-danger" role="alert">'.$e->getMessage().'</div>';
    }

    print "</div>";
}

if (isset($_GET['account'])) {
    print "<div class='container'>";
    print "<h2>Modification du compte ".$account->getUid()."</h2><hr>";

    print"<div class=\"alert alert-info\" role=\"alert\">Modifiez les champs que vous d&eacute;sirez changer.<br /> [*] indique ceux qui ne doivent pas &ecirc;tre nuls.<br />Vous pouvez r&eacute;initialiser le mot de passe si besoin.</div>";

    print "<form name='add' action='compte.php?domain=".$domain->getName()."&account=".$account->getUid()."' method='post' class='form-horizontal'>";

    print "<div class='form-group'>";
    print "<label for='cn' class='col-sm-3 control-label'>Nom Complet [*] :</label>";
    print "<div class='col-sm-7'><input type='text' name='cn' class='form-control' value='".$account->getName()."' /></div>";
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

    print "<div class='well'>Ajoutez/modifiez/supprimez les alias (mails accept&eacute;s en entr&eacute;e).<br />Un minimum d'un alias est requis. M&ecirc;mes instructions<br />pour les redirections (compte(s) dans le(s)quel(s) est/sont d&eacute;livr&eacute;(s) les mails).</div>";
    foreach ($account->getAliases() as $aliase) {
        print "<div class='form-group'>";
        print "<label for='mailaccept[]' class='col-sm-3 control-label'>Mail accept&eacute; en entr&eacute;e : </label>";
        print "<div class='col-sm-7'><input type='text' name='mailaccept[]' value='".$aliase."' class='form-control' /></div>";
        print "<div class='col-sm-2 control-label'>";
            if (!$conf['domaines']['onlyone']) {
                print "@" .$domain->getName();
            }
        print "</div>";
        print "</div>";

    }
    
    print "<div class='form-group'>";
    print "<label for='mailaccept[]' class='col-sm-3 control-label'>Cr&eacute;ation d'un nouveau mail accept&eacute; en entr&eacute;e : </label>";
    print "<div class='col-sm-7'><input type='text' name='mailaccept[]' value='' class='form-control' /></div>";
    print "<div class='col-sm-2 control-label'>";
        if (!$conf['domaines']['onlyone']) {
            print "@" .$domain->getName();
        }
    print "</div>";
    print "</div>";
    

    foreach ($account->getRedirections() as $red) {
        print "<div class='form-group'>";
        print "<label for='maildrop[]' class='col-sm-3 control-label'>Mails entrants redirig&eacute;s vers : </label>";
        print "<div class='col-sm-7'><input type='text' name='maildrop[]' value='".$red."' class='form-control' /></div>";
        print "<div class='col-sm-2 control-label'>";
            if (!$conf['domaines']['onlyone']) {
                print "@" .$domain->getName();
            }
        print "</div>";
        print "</div>";
    }

    print "<div class='form-group'>";
    print "<label for='maildrop[]' class='col-sm-3 control-label'>Nouvelle redirection vers : </label>";
    print "<div class='col-sm-7'><input type='text' name='maildrop[]' class='form-control' /></div>";
    print "<div class='col-sm-2 control-label'></div>";
    print "</div>";
    
    print "<hr><h5>Modifiez les autorisations du compte si besoin.</h5>";

    $isactive = ($account->isActive()) ? 'checked=checked' : '';
    print "<div class='form-group'>";
    print "<label for='isactive' class='col-sm-3 control-label'>Activation globale : </label>";
    print "<div class='col-sm-7'><input type='checkbox' name='isactive' $isactive class='form-control move-left' /></div>";
    print "<div class='col-sm-2 control-label'></div>";
    print "</div>";

    $isadmin = ($account->isAdmin()) ? 'checked="checked"' : '';
    print "<div class='form-group'>";
    print "<label for='isadmin' class='col-sm-3 control-label'>Compte admin : </label>";
    print "<div class='col-sm-7'><input type='checkbox' name='isadmin' $isadmin class='form-control move-left' /></div>";
    print "<div class='col-sm-2 control-label'></div>";
    print "</div>";

    $courieractive = ($account->isCourier()) ? 'checked="checked"' : '';
    print "<div class='form-group'>";
    print "<label for='courieractive' class='col-sm-3 control-label'>Utilisation POP/IMAP : </label>";
    print "<div class='col-sm-7'><input type='checkbox' name='courieractive' $courieractive class='form-control move-left' /></div>";
    print "<div class='col-sm-2 control-label'></div>";
    print "</div>";

    $authsmtpactive = ($account->isAuthSmtp()) ? 'checked="checked"' : '';
    print "<div class='form-group'>";
    print "<label for='authsmtpactive' class='col-sm-3 control-label'>Authentification SMTP : </label>";
    print "<div class='col-sm-7'><input type='checkbox' name='authsmtpactive' $authsmtpactive class='form-control move-left' /></div>";
    print "<div class='col-sm-2 control-label'></div>";
    print "</div>";

    #$amavisBypassSpamChecks= ($account->isAmavis())) ? 'checked="checked"' : '';
    #print "<div class='form-group'>";
    #print "<label for='amavisBypassSpamChecks' class='col-sm-3 control-label'>Désactivation Antispam : </label>";
    #print "<div class='col-sm-7'><input type='checkbox' name='amavisBypassSpamChecks' $amavisBypassSpamChecks class='form-control move-left' /></div>";
    #print "<div class='col-sm-2 control-label'></div>";
    #print "</div>";

    print "<div class='text-center'><button type='submit' class='btn btn-primary' onclick='return submit_add();'>Valider</button></div>";

    print '</form>';
    print '</div>';
} else {
?>

<div class="container">
    
<h2>Ajout d'un compte</h2><hr>

<form name="add" action="compte.php?domain=<?php print $domain->getName(); ?>" method="post" class="form-horizontal">
<div class="alert alert-info" role="alert">Remplissez lez champs, ceux contenant [*] sont obligatoires.</div>


<div class="form-group">
    <label for="uid" class="col-sm-3 control-label">Login [*] :</label>
    <div class="col-sm-7"><input type="text" name="uid" class="form-control" /></div>
    <div class="col-sm-2 control-label"><?php if (!$conf['domaines']['onlyone']) { print "@" .$domain->getName(); } ?></div>
</div>

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
<hr><h5>En plus du mail utilisant le login, vous pouvez ajouter des alias.</h5>

<div class="form-group">
    <label for="alias"     class="col-sm-3 control-label">Alias :</label>
    <div class="col-sm-7"><input type="text" name="alias[0]" class="form-control" /></div>
    <div class="col-sm-2 control-label"><?php if (!$conf['domaines']['onlyone']) { print "@" .$domain->getName(); } ?></div>
</div>

<div class="form-group">
    <label for="alias[1]"     class="col-sm-3 control-label">Alias :</label>
    <div class="col-sm-7"><input type="text" name="alias[1]" class="form-control" /></div>
    <div class="col-sm-2 control-label"><?php if (!$conf['domaines']['onlyone']) { print "@" .$domain->getName(); } ?></div>
</div>

<div class="form-group">
    <label for="alias[2]"     class="col-sm-3 control-label">Alias :</label>
    <div class="col-sm-7"><input type="text" name="alias[2]" class="form-control" /></div>
    <div class="col-sm-2 control-label"><?php if (!$conf['domaines']['onlyone']) { print "@" .$domain->getName(); } ?></div>
</div>

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


<div class="form-group">
    <label for="amavisBypassSpamChecks"     class="col-sm-3 control-label">Désactivation Antispam :</label>
    <div class="col-sm-7"><input type='checkbox' name='amavisBypassSpamChecks' <?php if ($conf['evoadmin']['amavisBypassSpamChecks']) print "checked" ?> class="form-control move-left" /></div>
    <div class="col-sm-2 control-label"></div>
</div>


<div class="text-center"><button type="submit" class="btn btn-primary" onclick='return submit_add();'>valider</button></div>

</form>

</div>

<?php } include('inc/fin.php'); ?>

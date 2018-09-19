<?php

// Load config and autoload class
require_once("lib/config.php");

// Force authentication on this page
require_once("lib/auth.php");

if (!empty($_POST['cn'])) {
    //  Verification coherence des mots de passe    
    if ( $_POST['pass1'] != $_POST['pass2'] ) {
        $alerts[] = array('type' => 2, 'message' => "Erreur, vous avez tape deux mots de passe differents");
        exit(1);
    }

    $uid = (!empty($_GET['account'])) ? $account->getUid() : htmlentities(strip_tags($_POST['uid']),ENT_NOQUOTES);
    $cn = htmlentities(strip_tags($_POST['cn']),ENT_NOQUOTES);
    $password = (!empty($_POST['pass1'])) ? $_POST['pass1'] : NULL; 
    $actif = (!empty($_POST['isactive'])) ? true : false;
    $admin = (!empty($_POST['isadmin'])) ? true : false;
    $courier = (!empty($_POST['courieractive'])) ? true : false;
    $webmail = (!empty($_POST['webmailactive'])) ? true : false;
    $authsmtp = (!empty($_POST['authsmtpactive'])) ? true : false;

    try {
        if (!empty($_GET['account'])) {
            $alerts[] = array('type' => 1, 'message' => "Modification en cours...");
            $account->update($cn,$password,$actif,$admin,$actif,$courier,$webmail,$authsmtp);
            header('Location: compte.php?domain='.$domain->getName().'&account='.$account->getUid());
        } else {
            $alerts[] = array('type' => 1, 'message' => "Ajout en cours...");
            $domain->addAccount($uid,$cn,$password,$actif,$admin,$actif,$courier,$webmail,$authsmtp);
            $alerts[] = array('type' => 0, 'message' => 'Ajout effectué <a href="compte.php?domain='.$domain->getName().'&account='.$uid.'@'.$domain->getName().'"><button class="btn btn-primary">Voir le compte créé</button></a>');
        }
    } catch (Exception $e) {
        $alerts[] = array('type' => 2, 'message' => $e->getMessage());
    }
}

print $twig->render('account.html', array(
    'page_name' => $config['global']['name']
    ,'alerts' => $alerts
    ,'login' => $server->getLogin()
    ,'isSuperAdmin' => $server->isSuperAdmin()
    ,'domain' => $domain->getName()
    ,'uid' => !empty($account) ? $account->getUid() : NULL
    ,'name' => !empty($account) ? $account->getName() : NULL
    ,'aliases' => !empty($account) ? $account->getAliases() : array()
    ,'maildrops' => !empty($account) ? $account->getRedirections() : array()
    ,'active' => !empty($account) ? $account->isActive() : true
    ,'admin' => !empty($account) ? $account->isAdmin() : false
    ,'courier' => !empty($account) ? $account->isCourier() : true
    ,'webmail' => !empty($account) ? $account->isWebmail() : true
    ,'authsmtp' => !empty($account) ? $account->isAuthSmtp() : true
));

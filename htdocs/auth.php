<?php

// Load config and autoload class
require_once("lib/config.php");

session_name('EVOADMIN_SESS');
session_start();

ob_start();
include('inc/haut.php');

if (isset($_SESSION['login'])) {
    print "<div class='login-info bg-info'>D&eacute;connecté</div>";
    session_unset('EVOADMIN_SESS');
    session_destroy();
}

if (!empty($_POST['login'])) {
    if ($server = new LdapServer(Html::clean($_POST['login']),  LDAP_BASE, LDAP_ADMIN_DN, LDAP_ADMIN_PASS, LDAP_URI)) {
        if ($server->login(Html::clean($_POST['password']))) {
            $_SESSION['login'] = $server->getLogin();
            $_SESSION['dn'] = $server->getDn();
            header("location: superadmin.php\n\n");
            exit(0);
        } else {
            print "<div class='alert alert-danger' role='alert'>&Eacute;chec de l\'authentification, utilisateur ou mot de passe incorrect.<br />Si vous avez oubli&eacute; votre mot de passe, contactez <a href='mailto:" .$conf['admin']['mail']. "'>" .$conf['admin']['mail']. "</a></div>";
        }
    } else {
        print "<div class=\"alert alert-danger\" role=\"alert\">Erreur de connexion LDAP !</div>";
    }
}
?>

<div class="loginpage">
    <div class="loginbox">
        <div class="illustration">
            <img src="img/logo.png" class="img-responsive" alt="Responsive image">
        </div>
        <form method="POST" action="auth.php" method="post" name="auth">
            <div class="form-group has-feedback has-feedback-left">
                <input type="text" name="login" class="form-control" placeholder="Utilisateur" autofocus="autofocus"/>
                <i class="glyphicon glyphicon-user form-control-feedback"></i>
            </div>   
            <div class="form-group has-feedback has-feedback-left">
                <input type="password" name="password"  class="form-control" placeholder="Mot de passe" />
                <i class="glyphicon glyphicon-lock form-control-feedback"></i>
            </div>
            <div class="form-group text-center">
                <button type="submit" class="btn btn-primary" onclick="return submit_login();">Connexion</button>
            </div>
        </form>
    </div>
</div>

<?php 

include('inc/fin.php'); 
ob_end_flush();

?>

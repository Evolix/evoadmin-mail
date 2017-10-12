<?php

/*
 * Authentification page
 *
 * Copyright (c) 2004-2006 Evolix - Tous droits reserves
 * $Id: auth.php,v 1.5 2008-09-21 03:28:03 gcolpart Exp $
 *
 * @author Gregory Colpart <reg@evolix.fr>
 * @version 1.0
 */

/**
 * Path
 */
define('EVOADMIN_BASE','./');

/**
 * Requires
 */
require_once EVOADMIN_BASE . 'common.php';

/*
 * Functions
 */

/**
 * Display message for bad authentification
 *
 * @param NULL
 * @return NULL
 */
function badauth() {
    global $conf;
    display("<div class='alert alert-danger' role='alert'>&Eacute;chec de l\'authentification, utilisateur ou mot de passe incorrect.<br />Si vous avez oubli&eacute; votre mot de passe, contactez <a href='mailto:" .$conf['admin']['mail']. "'>" .$conf['admin']['mail']. "</a></div>");
}


// we start PHP output buffering to use HTTP header later
ob_start();

/**
 * Requires and includes
 */
include EVOADMIN_BASE . 'haut.php';

/**
 * PHP cookies session
 * (cookies must be actived on browser)
 *
 * 3 steps:
 * - We use current session (or create new): session_start()
 * - We destroy current session: session_unset() et session_destroy()
 * - We create a new (and then empty) session: session_start()
 */
session_name('EVOADMIN_SESS');
session_start();
if (isset($_SESSION['login'])) {
    display("<div class='login-info bg-info'>D&eacute;connecté</div>");
}
session_unset('EVOADMIN_SESS');
session_destroy();
session_name('EVOADMIN_SESS');
session_start();

/**
 * Case with $_POST data
 * We try to verify login/password
 * and we forward to superadmin.php
 */
if (isset($_POST['login']))
{
    // connexion pour rechercher uid
    $ldapconn = Ldap::lda_connect(LDAP_ADMIN_DN,LDAP_ADMIN_PASS);

    if ($ldapconn)
    {
        $login = Html::clean($_POST['login']);
        $filter="(&(uid=" .$login. ")(isAdmin=TRUE))";
        $sr=ldap_search($ldapconn, LDAP_BASE, $filter);
        $info = ldap_get_entries($ldapconn, $sr);

        if ($info['count'])
        {
            $bind = @ldap_bind($ldapconn,$info[0]['dn'],$_POST['password']);
            if ($bind)
            {
                $_SESSION['login'] = $login;
                $_SESSION['dn'] = $info[0]['dn'];

                EvoLog::log("Login success for " . $login);
                header("location: superadmin.php\n\n");
                exit(0);
            }
            else
            {
                badauth();
                EvoLog::log("Password failed : " . $login);
                Formulaire();
            }
        }
        else
        {
            badauth();
            EvoLog::log("Login failed : " . $login);
            Formulaire();
        }
    }
/**
 * Case with no $_POST data
 * we display Formular
 */
} else {
?>
	<div class="loginpage">
		<div class="loginbox">
			<div class="illustration">
				<img src="img/logo.png" class="img-responsive" alt="Responsive image">
			</div>
			<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>"method="post" name="auth">
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
}

include EVOADMIN_BASE . 'fin.php';

ob_end_flush();

?>

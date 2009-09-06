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

    display("&Eacute;chec de l'authentification, utilisateur ou mot de passe incorrect.<br />
        Si vous avez oubli&eacute; votre mot de passe, contactez <a href='
        mailto:" .$conf['admin']['mail']. "'>" .$conf['admin']['mail']. "</a>");

}

/**
 * Display FORM HTML formular for connexion
 *
 * @param NULL
 * @return NULL
 */
function Formulaire() {

    ?>
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>"
            method="post" name="auth">

        <table width="100%">
        <tr>
        <td align="right" class="light"><b>Utilisateur</b></td>
        <td align="left" class="light">
        <input type="text" tabindex="1" name="login" value="" />
        </td>
        </tr>
        <tr>
        <td align="right" class="light"><b>Mot de passe</b></td>
        <td align="left"><input type="password" tabindex="2" name="password" /></td>
        </tr>
        <tr>
        <td>&nbsp;</td>
        <td align="left" class="light">
        <input type="submit" class="button" name="loginButton"
            tabindex="3" value="Connexion" onclick="return submit_login();" />
        </td>
        </tr>
        </table>

        <br /><br />
        <center><a href="/">Webmail</a></center>

        </form>

        </body>
        </html>

        <?php
}

// we start PHP output buffering to use HTTP header later
ob_start();

/**
 * Requires and includes
 */
include EVOADMIN_BASE . 'haut.php';
include EVOADMIN_BASE . 'inc/login.js';

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
    display("D&eacute;connexion");
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
    Formulaire();
}

include EVOADMIN_BASE . 'fin.php';

ob_end_flush();

?>

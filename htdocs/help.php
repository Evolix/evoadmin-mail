<?php

/**
 * French HTML help file  
 *
 * Copyright (c) 2004-2006 Evolix - Tous droits reserves
 * $Id: help.php,v 1.4 2009-09-06 01:12:07 gcolpart Exp $
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

    $rep = './';
    require_once($rep. 'lib/common.php');

    include($rep. 'inc/haut.php');

    $login = $_SESSION['login'];

    include($rep. 'inc/debut.php');

?>

<div class="container">
	<div class="alert alert-info" role="alert">Cette page devrait &ecirc;tre lue attentivement avant d'utiliser cette interface.</div>

	<hr><h2>Aide</h2>

	Cette aide devrait vous permettre de comprendre le fonctionnement de cette interface.

	<h3>Cr&eacute;er un compte mail</h3>

	<p>Vous devez choisir un mot de passe et &eacute;ventuellement des alias. Veillez &agrave; entrer des alias avec une syntaxe valide et un mot de passe valide et assez complexe. Vous pouvez &eacute;galement choisir de donner les droits "Admin" en cochant la case <i>Admin</i>, c'est-&agrave;-dire permettre &agrave; l'utilisateur de se connecter &agrave; cette interface pour pouvoir ajouter/supprimer des mails/alias.<br>
	<b>Vous devez &eacute;galement choisir un Login qui ne sera plus modifiable par la suite.</b> Ce login et le mot de passe serviront &agrave; se connecter au Webmail, serveur SMTP, serveur POP et serveur IMAP et &eacute;ventuellement &agrave; cette interface (si l'utilisateur a les droits "Admin").
	</p>

	<h3>Cr&eacute;er un alias</h3>

	<p>Vous devez choisir un alias et un mail valide pour rediriger les mails. Prenez bien garde &agrave; entrer un mail valide sinon les mails ne vous parviendront jamais.</p>

	<hr><h2>FAQ</h2>

	Cette Foire-Aux-Questions (FAQ) devrait r&eacute;pondre &agrave; vos questions. Au fil du temps, de nouvelles questions/r&eacute;ponses seront ajout&eacute;es. Posez vos questions &agrave; <a href="mailto:<?php print $conf['admin']['mail'];?>"><?php print $conf['admin']['mail'];?></a>.

	<p>Qu'est-ce qu'une syntaxe valide pour un compte mail ?</p>

	<i>Le d&eacute;but du mail (avant le @), doit respecter les r&egrave;gles suivantes :
	<ul>
	<li>&ecirc;tre compris entre 3 et 30 caract&egrave;res</li>
	<li>n'avoir que des caract&egrave;res de types lettre minuscules ou chiffres</li>
	<li>les caract&egrave;res tiret (-), point (.) et underscore (_) sont permis sauf en d&eacute;but et fin</li>
	</ul>
	</i>

	<p>Qu'est-ce qu'un mot de passe valide ?</p>

	<i>Votre mot de passe doit r&eacute;pondre aux r&egrave;gles suivantes :
	<ul>
	<li>avoir entre 5 et 12 caract&egrave;res</li>
	<li>N'utiliser ques des caract&egrave;res imprimables c'est-&agrave;-dire des lettres (majuscules, minuscules ou accentu&eacute;es), des chiffres ou les caract&egrave;res suivants :
	<pre>[]!"#$%&'()*+,-./:;<=>?@\^_`{|}~</pre>
	</ul>
	</i>

	<p>Qu'est-ce qu'un mot de passe assez complexe ?</p>

	<i>Outre d'avoir un mot de passe assez long (voir question pr&eacute;c&eacute;dente), il est fortement conseill&eacute; d'utilis&eacute; au moins un chiffre, au moins une lettre minuscule, au moins une lettre majuscule et au moins un caract&egrave;res "sp&eacute;cial". De plus, l'utilisation de suites de caract&egrave;res "connues" (mots, dates, noms, etc.) est fortement d&eacute;conseill&eacute;e.</i>
	
</div>


                    
<?php

} //if (isset($_SESSION['login'])) 
else
{
    header("location: auth.php\n\n");
    exit(0);
}

include EVOADMIN_BASE . 'inc/fin.php';

?>

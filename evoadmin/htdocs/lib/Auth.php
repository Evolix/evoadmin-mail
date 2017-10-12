<?php

/**
 * Copyright (c) 2004-2005 Evolix - Tous droits reserves 
 * $Id: Auth.php,v 1.1 2009-09-02 16:22:45 gcolpart Exp $
 *
 * Fonctions utiles pour authentification
 * 
 */


class Auth
{



	/**
	 * Verifie qu'un login est incorrect
	 * entre 3 et 30 caractères
	 * en lettres minuscule, chiffres, '-', '.' ou '_'
	 * pour le premier et dernier caractères : seuls lettres et minuscules
	 * et chiffres sont possibles
	 */

	function badname($login)
	{
		return (!preg_match('/^([a-z0-9][a-z0-9\-\.\_]{1,28}[a-z0-9])$/',$login));
	}

	/**
	 * verifie qu'un mot de passe est incorrect
	 * entre 5 et 12 caractères
	 * caractères imprimables 
	 */

	function badpassword($pass)
	{
		return ( (strlen($pass) > 42) ||
				(strlen($pass) < 5) ||
				(!preg_match('/^([[:graph:]]*)$/',$pass)) );

	}

	/**
	 * verifie qu'un FQDN semble correct
	 */

	function badfqdn($domain)
	{
		return (!preg_match('/^([[:alnum:]\.\-]{2,70}.[[:alpha:]]{2,5})$/',$domain));
	}

}


?>

<?php

/**
 * Copyright (c) 2004-2006 Evolix - Tous droits reserves 
 * $Id: Html.php,v 1.1.1.1 2006-11-03 14:56:45 reg Exp $
 *
 * Evolib HTML (PHP4)
 * Fonctions utiles pour utilisation avec champs FORM
 * Fonctions diverses sur manipulation de caractères
 */

class Html
{

	/**
	 * Nettoie un texte
	 * Supprime toutes les balises HTML
	 */

	function justclean($texte)
	{   
		return strip_tags($texte);
	}

	/**
	 * Nettoie un texte
	 * Supprime toutes les balises HTML
	 * convertit en caracteres HTML 
	 * TODO : ENT_NOQUOTES ou ENT_QUOTES ??
	 */

	function clean($texte)
	{
		return htmlentities(strip_tags($texte),ENT_NOQUOTES);
	}

	/**
	 * Nettoie un texte en permettant l'utilisation de liens A HREF
	 * Supprime toutes les balises HTML
	 * convertit en caracteres HTML 
	 * permet l'utilisation de liens [Evolixn=http://www.evolix.fr]
	 * convertion automatique (inspire des liens SPIP, http://www.spip.net)
	 */

	function clean2($texte)
	{
		$texte = htmlentities(strip_tags($texte),ENT_NOQUOTES);
		$texte = ereg_replace('\[([^"^=]+)=(http://[^"^[:space:]]+)\]',
				'<a href="\\2">\\1</a>',$texte);
		return addslashes($texte);
	}

	/**
	 * Nettoie une requete SQL
	 * Ajoute des antislashes devant : guillements simples, doubles, antislashes
	 * caractere NULL
	 * Cette fonction n'agit que si la directive magic_quotes_gpc est sur Off
	 */

	function sqlclean($texte)
	{
		return (get_magic_quotes_gpc()) ? $texte : addslashes($texte);
	}


	function purgeaccents($texte)
	{

		// liste des caracteres accentuees
		$couple["é"] = "e"; $couple["è"] = "e"; $couple["ê"] = "e"; $couple["ë"] = "e";
		$couple["È"] = "e"; $couple["É"] = "e"; $couple["Ê"] = "e"; $couple["Ë"] = "e";
		$couple["à"] = "a"; $couple["ä"] = "a"; $couple["â"] = "a"; $couple["å"] = "a";
		$couple["á"] = "a"; $couple["ã"] = "a"; $couple["æ"] = "a";
		$couple["À"] = "a"; $couple["Á"] = "a"; $couple["Â"] = "a"; $couple["Ã"] = "a";
		$couple["Ä"] = "a"; $couple["Å"] = "a"; $couple["Æ"] = "a";
		$couple["ù"] = "u"; $couple["ü"] = "u"; $couple["û"] = "u"; $couple["ú"] = "u";
		$couple["ô"] = "o"; $couple["ö"] = "o"; $couple["ò"] = "o"; $couple["ó"] = "o"; $couple["õ"] = "o";
		$couple["î"] = "i"; $couple["ï"] = "i"; $couple["ì"] = "i"; $couple["í"] = "i";
		$couple["ç"] = "c"; $couple["Ç"] = "c";
		$couple["ÿ"] = "y";  $couple["ý"] = "y"; $couple["ñ"] = "n";

		while(list($car,$val) = each($couple))
		{
			$texte = ereg_replace($car,$val,$texte);
		}

		return $texte;

	}

	/**
	 * renvoie un entier
	 */

	function toint($var)
	{
		return number_format($var, 0, '', '');
	}
}
?>

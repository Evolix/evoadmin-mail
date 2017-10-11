<?php

/**
 * Copyright (c) 2004-2006 Evolix - Tous droits reserves
 * $Id: Math.php,v 1.1 2008-09-29 09:02:52 tmartin Exp $
 *
 * Fonctions mathematiques
 */


class Math
{

	function EvoFormat($param) 
	{
		// Pour eviter -0.00
		if ( number_format($param, 2, '.', ' ') == -0.00 ) $param = 0.00;

		return preg_replace('x','&nbsp;',number_format($param, 2, '.', 'x'));
	}

	function LongCode($param)
	{
		return sprintf("%03s", $param);
	}

	function LongId($param)
	{
		return sprintf("%08s", $param);
	}

    function arrondi($num) {
        //return number_format($num, 0, ',', '.');
        return number_format($num, 0, '', '');
    }

}
?>

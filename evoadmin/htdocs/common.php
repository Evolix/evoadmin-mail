<?php

/**
 * Copyright (c) 2004-2008 Evolix - Tous droits reserves
 * $Id: common.php,v 1.13 2009-02-21 03:55:15 gcolpart Exp $
 */

/**
 * common.php
 * file included in every PHP file
 */

/**
 * Functions
 */
function test_exist($file) {
    if(!file_exists($file)) {
        die("Erreur, vous devez mettre en place le fichier $file !\n");
    }
}

// We verify if 'install/' directory is removed
if (file_exists('install')) {
    die("Erreur 001 : logiciel non installe  ou repertoire 'install/' non
		    supprime !");
}

/**
 * Includes
 */

// PEAR libs
// change include_path for PEAR
// http://pear.php.net/manual/en/installation.shared.php
//if (!(ini_set('include_path', CONF_PWD . 'pear/' . PATH_SEPARATOR . ini_get('include_path'))))
if (!(ini_set('include_path', ini_get('include_path')))) {
    die('bibliotheques PEAR non presentes');
} else {

    require_once 'PEAR.php';
    require_once 'Log.php';

    // config files
    // (here because need Log PEAR lib)
    test_exist('connect.php');
    require_once('connect.php');
    test_exist('config/conf.php');
    require_once('config/conf.php');

    global $conf;

    // only for samba mode
    if (($conf['admin']['what'] == 2) || ($conf['admin']['what'] == 3)) {
        require_once 'Crypt/CHAP.php';
    }
}

// functions
require_once 'inc/functions.php';
if ($conf['admin']['use_hook']) {
    require_once 'inc/hook.php';
} else {
    require_once 'inc/hook-dist.php';
}

// evolibs
require_once 'evolibs/Ldap.php';
require_once 'evolibs/Html.php';
require_once 'evolibs/Math.php';
require_once 'evolibs/EvoLog.php';
require_once 'evolibs/Auth.php';


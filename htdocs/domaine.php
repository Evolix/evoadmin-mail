<?php

// Load config and autoload class
require_once("lib/config.php");

// Force authentication on this page
require_once("lib/auth.php");

if (!$server->isSuperAdmin()) {
    $alerts[] = array('type' => 2, 'message' => "Vous n'avez pas les droits pour cette page");
#    EvoLog::log("Access denied on domaine.php");
    exit(1);
}

$domain = NULL;

if (!empty($_POST['domain'])) {
    $domain = htmlentities(strip_tags($_POST['domain']),ENT_NOQUOTES);

    $alerts[] = array('type' => 1, 'message' => "Ajout en cours du domaine $domain ...");

    try {
        $active = (!empty($_POST['isactive'])) ? true : false;
        $server->addDomain($domain, $active);
        $alerts[] = array('type' => 0, 'message' => "Ajout effectué.");
    } catch (Exception $e_ad) {
        $alerts[] = array('type' => 2, 'message' => $e_ad->getMessage());
    }
}

print $twig->render('add_domain.html', array(
    'page_name' => $config['global']['name']
    ,'alerts' => $alerts
    ,'login' => $server->getLogin()
    ,'isSuperAdmin' => $server->isSuperAdmin()
));

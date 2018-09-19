<?php

// Load config and autoload class
require_once("lib/config.php");

// Force authentication on this page
require_once("lib/auth.php");

if (!empty($_POST['domain'])) {
    $domain = htmlentities(strip_tags($_POST['domain']),ENT_NOQUOTES);

    $alerts[] = array('type' => 1, 'message' => "Voulez vous vraiment supprimer le domaine $domain ?");
    $alerts[] = array('type' => 1, 'message' => "<form name=\"del\" method=\"post\" action=\"superadmin.php\"><button type=\"submit\" name=\"delete\" value=\"$domain\">Confirmer</button> / <a href=\"superadmin.php\">Annuler</a></form>");
}

if (!empty($_POST['delete'])) {
    $domain = htmlentities(strip_tags($_POST['delete']),ENT_NOQUOTES);
    $alerts[] = array('type' => 1, 'message' => "Suppression du domaine $domain ...");
    try {
        $server->delDomain($domain);
        $alerts[] = array('type' => 0, 'message' => 'Suppression effectué.');
    } catch (Exception $e_ad) {
        $alerts[] = array('type' => 2, 'message' => $e_ad->getMessage());
    }
}

print $twig->render('list_domain.html', array(
    'page_name' => $config['global']['name']
    ,'alerts' => $alerts
    ,'login' => $server->getLogin()
    ,'isSuperAdmin' => $server->isSuperAdmin()
    ,'domains' => $server->getDomains()
));

<?php

// Load config and autoload class
require_once("lib/config.php");

// Force authentication on this page
require_once("lib/auth.php");

print $twig->render('help.html', array(
    'page_name' => $config['global']['name']
    ,'alerts' => $alerts
    ,'login' => $server->getLogin()
    ,'isSuperAdmin' => $server->isSuperAdmin()
    ,'notif_mail' => $config['global']['mail']
));

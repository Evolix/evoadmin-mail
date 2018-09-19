<?php

// Load config and autoload class
require_once("lib/config.php");

// Force authentication on this page
require_once("lib/auth.php");

if (!empty($_POST['cn'])) {
    $cn = (!empty($_GET['alias'])) ? $alias->getName() : htmlentities(strip_tags($_POST['cn']),ENT_NOQUOTES); 
    $actif = (!empty($_POST['isactive'])) ? true : false;
    $mailaccept = array_filter($_POST['mailaccept'], function($value) {
        if (!empty($value)) {
            return true;
        } else {
            return false;
        }
    });
    array_walk($mailaccept, function(&$item,$key) {
        if (!empty($item)) {
            global $domain;
            $item = "$item". "@".$domain->getName();
        }
    });
    $maildrop = $_POST['maildrop'];

    try {
        if (!empty($_GET['alias'])) {
            $alerts[] = array('type' => 1, 'message' => "Modification en cours...");
            $alias->update($actif,$mailaccept,$maildrop);
            header('Location: alias.php?domain='.$domain->getName().'&alias='.$alias->getName());
        } else {
            $alerts[] = array('type' => 1, 'message' => "Ajout en cours...");
            $domain->addAlias($cn,$actif,$mailaccept,$maildrop);
            $alerts[] = array('type' => 0, 'message' => "Ajout effectué");
            $alerts[] = array('type' => 0, 'message' => '<a href="alias.php?domain='.$domain->getName().'&alias='.$cn.'"><button class="btn btn-primary">Voir l\'alias cr&eacute;&eacute;</button></a>');
        }
    } catch (Exception $e) {
       $alerts[] = array('type' => 2, 'message' => $e->getMessage());
    }

}

print $twig->render('alias.html', array(
    'page_name' => $config['global']['name']
    ,'alerts' => $alerts
    ,'login' => $server->getLogin()
    ,'isSuperAdmin' => $server->isSuperAdmin()
    ,'domain' => $domain->getName()
    ,'name' => !empty($alias) ? $alias->getName() : NULL
    ,'active' => !empty($alias) ? $alias->isActive() : true
    ,'aliases' => !empty($alias) ? $alias->getAliases() : NULL
    ,'maildrops' => !empty($alias) ? $alias->getRedirections() : NULL
));

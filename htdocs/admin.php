<?php

// Load config and autoload class
require_once("lib/config.php");

// Force authentication on this page
require_once("lib/auth.php");

if (empty($_GET['domain'])) {
    header("location: superadmin.php\n\n");
    exit(1);
}

if (!empty($_POST['account'])) {
    $account = htmlentities(strip_tags($_POST['account']),ENT_NOQUOTES);
    $alerts[] = array('type' => 1, 'message' => "Voulez vous vraiment supprimer le compte $account ?");
    $alerts[] = array('type' => 1, 'message' => "<form name=\"del\" method=\"post\" action=\"admin.php?domain=".$domain->getName()."&viewonly=1\"><button type=\"submit\" name=\"delete\" value=\"$account\">Confirmer</button> / <a href=\"admin.php?domain=".$domain->getName()."&viewonly=1\">Annuler</a></form>");
}

if (!empty($_POST['alias'])) {
    $alias = htmlentities(strip_tags($_POST['alias']),ENT_NOQUOTES);
    $alerts[] = array('type' => 1, 'message' => "Voulez vous vraiment supprimer l'alias $alias ?");
    $alerts[] = array('type' => 1, 'message' => "<form name=\"del\" method=\"post\" action=\"admin.php?domain=".$domain->getName()."&viewonly=2\"><button type=\"submit\" name=\"delalias\" value=\"$alias\">Confirmer</button> / <a href=\"admin.php?domain=".$domain->getName()."&viewonly=2\">Annuler</a></form>");
}

if (!empty($_POST['delete'])) {
    $account = htmlentities(strip_tags($_POST['delete']),ENT_NOQUOTES);
    $alerts[] = array('type' => 1, 'message' => "Suppression du compte $account...");
    try {
        $domain->delAccount($account);
        $alerts[] = array('type' => 0, 'message' => "Suppression effectué.");
    } catch (Exception $e) {
        $alerts[] = array('type' => 2, 'message' => $e->getMessage());
    }
}

if (!empty($_POST['delalias'])) {
    $alias = htmlentities(strip_tags($_POST['delalias']),ENT_NOQUOTES);
    $alerts[] = array('type' => 1, 'message' => "Suppression de l'alias $alias...");
    try {
        $domain->delAlias($alias);
        $alerts[] = array('type' => 0, 'message' => "Suppression effectué.");
    } catch (Exception $e) {
        $alerts[] = array('type' => 2, 'message' => $e->getMessage());
    }
}

if (!empty($_POST['isactive']) && $server->isSuperAdmin()) {
    $active = ($_POST['isactive'] == "TRUE") ? true : false;
    try {
        $domain->update($active);
        header('Location: admin.php?domain='.$domain->getName());
    } catch (Exception $e) {
        $alerts[] = array('type' => 2, 'message' => $e->getMessage());
    }
}

if ( (!isset($_GET['viewonly'])) || ($_GET['viewonly']==1) ) {

print $twig->render('list_account.html', array(
    'page_name' => $config['global']['name']
    ,'alerts' => $alerts
    ,'login' => $server->getLogin()
    ,'isSuperAdmin' => $server->isSuperAdmin()
    ,'domain' => $domain->getName()
    ,'active' => $domain->isActive()
    ,'accounts' => $domain->getAccounts()
    ,'view' => 'account'
));

} elseif ( (isset($_GET['viewonly'])) && ($_GET['viewonly']==2) ) {

print $twig->render('list_alias.html', array(
    'page_name' => $config['global']['name']
    ,'alerts' => $alerts
    ,'login' => $server->getLogin()
    ,'isSuperAdmin' => $server->isSuperAdmin()
    ,'domain' => $domain->getName()
    ,'active' => $domain->isActive()
    ,'aliases' => $domain->getAlias()
    ,'view' => 'alias'
));

}

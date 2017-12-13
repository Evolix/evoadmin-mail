<?php

// Load config and autoload class
require_once("lib/config.php");

// Force authentication on this page
require_once("lib/auth.php");

require_once("lib/common.php");

include("inc/haut.php");
include("inc/debut.php");

if (!$server->isSuperAdmin()) {
    print "<div class=\"alert alert-danger\" role=\"alert\">Vous n'avez pas les droits pour cette page</div>";
#    EvoLog::log("Access denied on domaine.php");
    include("inc/fin.php");
    exit(1);
}

// Ajouter un domaine
if (!empty($_POST['domain'])) {
    $domain = Html::clean($_POST['domain']); 
    
    print "<div class='container'>";
    print "<div class=\"alert alert-warning\" role=\"alert\">Ajout en cours du domaine ".$domain." ...</div>";
    
    try {
        $server->addDomain(Html::clean($_POST['domain'], Html::clean($_POST['is_active'])));
        domain_add($domain);
        print '<div class="alert alert-success" role="alert">Ajout effectu&eacute;.</div>';
        #EvoLog::log("Add domain ".$domain);
        domainnotify($domain); 
    } catch (Exception $e_ad) {
        print '<div class="alert alert-danger" role="alert">'.$e_ad->getMessage().'</div>';
        #EvoLog::log("Add $domain failed");
    }
    
    print "</div>";
}

// Formulaire d'ajout d'un domaine
?>

<div class="container">    
    <h4>Ajout d'un domaine</h4>
    <form name="add" action="domaine.php" method="post" class="form-horizontal">
        <div class="alert alert-info" role="alert">Remplissez lez champs, ceux contenant [*] sont obligatoires.</div>
        <div class="form-group">
            <label for="domain" class="col-sm-3 control-label">Domaine [*] :</label>
            <div class="col-sm-9"><input type="text" name="domain" class="form-control" /></div>
        </div>
        <div class="form-group">
            <label for="isactive" class="col-sm-3 control-label">Activation globale :</label>
            <div class="col-sm-9"><input type='checkbox' name='isactive' checked  class="form-control move-left"/></div>
        </div>
        <div class="text-center"><button type="submit" class="btn btn-primary">Valider</button></div>
    </form>
</div>
   
<?php include("inc/fin.php"); ?>

<?php

// Load config and autoload class
require_once("lib/config.php");

// Force authentication on this page
require_once("lib/auth.php");

include('inc/haut.php');
include('inc/debut.php');


if (!empty($_POST['domain'])) {
    $domain = Html::clean($_POST['domain']);

    print '<div class="container"><form name="del "method="post" action="superadmin.php">';
    print '<div class="alert alert-warning" role="alert">Voulez vous vraiment supprimer le domaine '.$domain.' ?</div>';
    print '<div class="alert alert-warning" role="alert"><button type="submit" name="delete" value="'.$domain.'">Confirmer</button> / <a href="superadmin.php">Annuler</a></div>';
    print '</form></div>';
}

if (!empty($_POST['delete'])) {
    $domain = Html::clean($_POST['delete']);
    print '<div class="container">';
    print '<div class="alert alert-warning" role="alert">Suppression du domaine '.$domain.' ...</div>';
    try {
        $server->delDomain(Html::clean($_POST['delete']));
        print '<div class="alert alert-success" role="alert">Suppression effectu&eacute;.</div>';
    } catch (Exception $e_ad) {
        print '<div class="alert alert-danger" role="alert">'.$e_ad->getMessage().'</div>';
    }
    print '</div>';
}

?>

<div class="container">
    <h2>Liste des domaines administrables :</h2><hr>
    <form name="del" method="post" action="superadmin.php">
    <table class="table table-striped table-condensed">
        <thead>
            <tr>
                <th>Nom du domaine</th>
                <th width="80px">Actif</th>
                <th>Nombre de comptes</th>
                <th>dont comptes mail</th>
                <th>Nombre d'alias mail</th>
                <th>Taille / Quota</th>
                <th width="50px">Suppr.</th>
            </tr>
        </thead>
        <tbody>
        <?php
        // lignes avec les details sur les domaines
        $domains = $server->getDomains();
        foreach ($domains as $domain) {
            print '<tr><td style="text-align:left;"><a href="admin.php?domain='.$domain->getName(). '">' .$domain->getName(). '</a></td>';
            if ($domain->isActive()) {
                print '<td>Oui</td>';
            } else {
                print '<td>Non</td>';
            }
            print '<td><b>' .$domain->getNbAccounts(). '</b></td>';
            print '<td><b>' .$domain->getNbMailAccounts(). '</b></td>';
            //print '<td><b>' .$domain->getNbSmbAccounts(). '</b></td>';
            print '<td><b>' .$domain->getNbMailAlias(). '</b></td>';
            print '<td>' .$domain->getQuota(). '</td>';
            print '<td><button type="submit" name="domain" value="'.$domain->getName().'"><span class="glyphicon glyphicon-trash"></span></button></td>';
            print '</tr>';
        }
        ?>
        </tbody>
    </table>
    </form>
</div>

<?php include('inc/fin.php'); ?>

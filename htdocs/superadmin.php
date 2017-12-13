<?php

// Load config and autoload class
require_once("lib/config.php");

// Force authentication on this page
require_once("lib/auth.php");

include('inc/haut.php');
include('inc/debut.php');

?>

<div class="container">
    <h2>Liste des domaines administrables :</h2><hr>
    <table class="table table-striped table-condensed">
        <thead>
            <tr>
                <th>Nom du domaine</th>
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
            print '<td><b>' .$domain->getNbAccounts(). '</b></td>';
            print '<td><b>' .$domain->getNbMailAccounts(). '</b></td>';
            //print '<td><b>' .$domain->getNbSmbAccounts(). '</b></td>';
            print '<td><b>' .$domain->getNbMailAlias(). '</b></td>';
            print '<td>' .$domain->getQuota(). '</td>';
            print '<td>';
            print '<a href="domaine.php?del=' .$domain->getName(). '"><span class="glyphicon glyphicon-trash"></span></a>';
            print '</td></tr>';
        }
        ?>
        </tbody>
    </table>
</div>

<?php include('inc/fin.php'); ?>

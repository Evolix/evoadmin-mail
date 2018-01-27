<?php

// Load config and autoload class
require_once("lib/config.php");

// Force authentication on this page
require_once("lib/auth.php");

if (empty($_GET['domain'])) {
    header("location: superadmin.php\n\n");
    exit(1);
}

include("inc/haut.php");
include("inc/debut.php");

if (!empty($_POST['account'])) {
    $account = htmlentities(strip_tags($_POST['account']),ENT_NOQUOTES);

    print '<div class="container"><form name="del "method="post" action="admin.php?domain='.$domain->getName().'">';
    print '<div class="alert alert-warning" role="alert">Voulez vous vraiment supprimer le compte '.$account.' ?</div>';
    print '<div class="alert alert-warning" role="alert"><button type="submit" name="delete" value="'.$account.'">Confirmer</button> / <a href="admin.php?domain='.$domain->getName().'">Annuler</a></div>';
    print '</form></div>';
}

if (!empty($_POST['alias'])) {
    $alias = htmlentities(strip_tags($_POST['alias']),ENT_NOQUOTES);

    print '<div class="container"><form name="del "method="post" action="admin.php?domain='.$domain->getName().'&viewonly=2">';
    print '<div class="alert alert-warning" role="alert">Voulez vous vraiment supprimer l\'alias '.$alias.' ?</div>';
    print '<div class="alert alert-warning" role="alert"><button type="submit" name="delalias" value="'.$alias.'">Confirmer</button> / <a href="admin.php?domain='.$domain->getName().'&viewonly=2">Annuler</a></div>';
    print '</form></div>';
}

if (!empty($_POST['delete'])) {
    $account = htmlentities(strip_tags($_POST['delete']),ENT_NOQUOTES);
    print '<div class="container">';
    print '<div class="alert alert-warning" role="alert">Suppression du compte '.$account.' ...</div>';
    try {
        $domain->delAccount($account);
        print '<div class="alert alert-success" role="alert">Suppression effectu&eacute;.</div>';
    } catch (Exception $e) {
        print '<div class="alert alert-danger" role="alert">'.$e->getMessage().'</div>';
    }
    print '</div>';
}

if (!empty($_POST['delalias'])) {
    $alias = htmlentities(strip_tags($_POST['delalias']),ENT_NOQUOTES);
    print '<div class="container">';
    print '<div class="alert alert-warning" role="alert">Suppression de l\'alias '.$alias.' ...</div>';
    try {
        $domain->delAlias($alias);
        print '<div class="alert alert-success" role="alert">Suppression effectu&eacute;.</div>';
    } catch (Exception $e) {
        print '<div class="alert alert-danger" role="alert">'.$e->getMessage().'</div>';
    }
    print '</div>';
}

if (!empty($_POST['isactive']) && $server->isSuperAdmin()) {
    $active = ($_POST['isactive'] == "TRUE") ? true : false;
    try {
        $domain->update($active);
        header('Location: admin.php?domain='.$domain->getName());
    } catch (Exception $e) {
        print '<div class="alert alert-danger" role="alert">'.$e->getMessage().'</div>';
    }
}

?>
<div class="container">
    <div class="text-center">
    <?php
    print '<form name="update" method="post" action="admin.php?domain='.$domain->getName().'">';
    if ($server->isSuperAdmin()) {
        if (!$domain->isactive()) {
            print '<button type="submit" name="isactive" value="TRUE" class="btn btn-primary">Activer le domaine</button>&nbsp;&nbsp;&nbsp;';
        } else {
            print '<button type="submit" name="isactive" value="FALSE" class="btn btn-primary">Désactiver le domaine</button>&nbsp;&nbsp;&nbsp;';
        }
    }
    ?>
    <a href="compte.php?domain=<?php print $domain->getName() ?>"><button type="button" class="btn btn-primary">Ajouter un nouveau compte</button></a>&nbsp;&nbsp;&nbsp;

    <?php
        $viewonly1= ( (isset($_GET['viewonly'])) && ($_GET['viewonly']==2) ) ? "" : "selected='selected'";
        $viewonly2= ( (isset($_GET['viewonly'])) && ($_GET['viewonly']==2) ) ? "selected='selected'" : "";
    ?>

        <a href="alias.php?domain=<?php print $domain->getName() ?>"><button type="button" class="btn btn-primary">Ajouter un nouvel alias/groupe de diffusion</button></a>
    </form>
    </div>
        <hr>
        <form class='center' action='admin.php' method='GET' name='listing'>
            <div class="form-group">
                <input type="hidden" name="domain" value="<?php print $domain->getName() ?>"/>
                <select class="form-control" name='viewonly' onchange="document.listing.submit()">
                    <option value='1' <?php print $viewonly1; ?>>Liste des comptes</option>
                    <option value='2' <?php print $viewonly2; ?>>Liste des alias/groupe de diffusion</option>
                </select>
            </div>
        </form>
    <?php

        if ( (!isset($_GET['viewonly'])) || ($_GET['viewonly']==1) ) {

    ?>

        <h2>Liste des comptes :</h2><hr>

        <form name="del" method="post" action="admin.php?domain=<?php print $domain->getName(); ?>">
        <table class="table table-striped table-condensed">
            <thead>
                <tr>
                    <th><strong>Nom du compte</strong></th>
                    <th width="100px">Actif</th>
                    <th width="100px">Admin</th>
                    <th width="100px">POP / IMAP</th>
                    <th width="100px">Auth SMTP</th>
                    <th>Quota</th>
                    <th width="50px">Suppr</th>
                </tr>
            </thead>
            <tbody>

         <?php
            $accounts = $domain->getAccounts();
            foreach ($accounts as $account) {
                print '<tr><td style="text-align:left;"><a href="compte.php?domain='.$domain->getName().'&account='.$account->getUid().'">' .$account->getName().' &lt;'.$account->getUid().'&gt;</a></td>';
                if ($account->isActive()) {
                    print '<td><span class="glyphicon glyphicon-ok"></span></td>';
                } else {
                    print '<td><span class="glyphicon glyphicon-remove"></span></td>';
                }
                if ($account->isAdmin()) {
                    print '<td><span class="glyphicon glyphicon-ok"></span></td>';
                } else {
                    print '<td><span class="glyphicon glyphicon-remove"></span></td>';
                }
                if ($account->isCourier()) {
                    print '<td><span class="glyphicon glyphicon-ok"></span></td>';
                } else {
                    print '<td><span class="glyphicon glyphicon-remove"></span></td>';
                }
                if ($account->isAuthSmtp()) {
                    print '<td><span class="glyphicon glyphicon-ok"></span></td>';
                } else {
                    print '<td><span class="glyphicon glyphicon-remove"></span></td>';
                }
                print '<td>'.$account->getQuota().'</td>';
                print '<td><button type="submit" name="account" value="'.$account->getUid().'"><span class="glyphicon glyphicon-trash"></span></button></td>';
                print '</tr>';
            }
            print "</tbody></table></form>";
       } elseif ( (isset($_GET['viewonly'])) && ($_GET['viewonly']==2) ) {

    ?>

        <h2>Liste des alias/groupe de diffusion&nbsp;:</h2>

        <form name="del" method="post" action="admin.php?domain=<?php print $domain->getName(); ?>&viewonly=2">
        <table class="table table-striped table-condensed">
            <thead>
                <tr>
                <th><strong>Nom de l'alias/groupe de diffusion</strong></th>
                <th width="100px">Actif</th>
                <th width="50px">Suppr</th>
                </tr>
            </thead>
            <tbody>


        <?php
            $aliases = $domain->getAlias();
            foreach ($aliases as $alias) {
                print '<tr><td style="text-align:left;"><a href="alias.php?domain='.$domain->getName().'&alias='.$alias->getName(). '">' .$alias->getname(). '</a></td>';
                if ($alias->isActive()) {
                    print '<td><span class="glyphicon glyphicon-ok"></span></td>';
                } else {
                    print '<td><span class="glyphicon glyphicon-remove"></span></td>';
                }
                print '<td><button type="submit" name="alias" value="'.$alias->getName().'"><span class="glyphicon glyphicon-trash"></span></button></td>';
                print '</tr>';
            }
            print "</tbody></table></form>";
        }
    ?>

</div>

<?php include("inc/fin.php"); ?>

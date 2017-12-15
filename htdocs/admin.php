<?php

// Load config and autoload class
require_once("lib/config.php");

// Force authentication on this page
require_once("lib/auth.php");

require_once("lib/common.php");

if (empty($_GET['domain'])) {
    header("location: superadmin.php\n\n");
    exit(1);
}

include("inc/haut.php");
include("inc/debut.php");

if (!empty($_POST['account'])) {
    $account = Html::clean($_POST['account']);

    print '<div class="container"><form name="del "method="post" action="admin.php?domain='.$domain->getName().'">';
    print '<div class="alert alert-warning" role="alert">Voulez vous vraiment supprimer le compte '.$account.' ?</div>';
    print '<div class="alert alert-warning" role="alert"><button type="submit" name="delete" value="'.$account.'">Confirmer</button> / <a href="admin.php?domain='.$domain->getName().'">Annuler</a></div>';
    print '</form></div>';
}

if (!empty($_POST['delete'])) {
    $account = Html::clean($_POST['delete']);
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


?>
<div class="container">
    <div class="text-center">
    <a href="compte.php?domain=<?php print $domain->getName() ?>"><button class="btn btn-primary">Ajouter un nouveau compte</button></a>&nbsp;&nbsp;&nbsp;

    <?php
        // only for mail mode
        if (($conf['admin']['what'] == 1) || ($conf['admin']['what'] == 3)) {

        $viewonly1= ( (isset($_GET['viewonly'])) && ($_GET['viewonly']==2) ) ? "" : "selected='selected'";
        $viewonly2= ( (isset($_GET['viewonly'])) && ($_GET['viewonly']==2) ) ? "selected='selected'" : "";
    ?>

        <a href="alias.php?domain=<?php print $domain->getName() ?>"><button class="btn btn-primary">Ajouter un nouvel alias/groupe de diffusion</button></a>
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
        <form name="del" method="post" action="admin.php?domain=<?php print $domain->getName(); ?>">
    <?php
        }

        if ( (!isset($_GET['viewonly'])) || ($_GET['viewonly']==1) ) {

    ?>

            <h2>Liste des comptes :</h2><hr>

        <table class="table table-striped table-condensed">
            <thead>
                <tr>
                    <th><strong>Nom du compte</strong></th>
                    <th>Quota</th>
                    <th width="50px">Suppr</th>
                </tr>
            </thead>
            <tbody>

         <?php
            $accounts = $domain->getAccounts();
            foreach ($accounts as $account) {
                print '<tr><td style="text-align:left;"><a href="compte.php?domain='.$domain->getName().'&account='.$account->getUid().'">' .$account->getName().' &lt;'.$account->getUid().'&gt;</a></td>';
                print '<td>' .getquota($account->getUid(),'user'). '</td>';
                print '<td><button type="submit" name="account" value="'.$account->getUid().'"><span class="glyphicon glyphicon-trash"></span></button></td>';
                print '</tr>';
            }
            print "</tbody></table>";
       } elseif ( (isset($_GET['viewonly'])) && ($_GET['viewonly']==2) ) {

    ?>

             <h2>Liste des alias/groupe de diffusion&nbsp;:</h2>

        <table class="table table-striped table-condensed">
            <thead>
                <tr>
                <th><strong>Nom de l'alias/groupe de diffusion</strong></th>
                <th width="50px">Suppr</th>
                </tr>
            </thead>
            <tbody>


        <?php
            $aliases = $domain->getAlias();
            foreach ($aliases as $alias) {
                print '<tr><td style="text-align:left;"><a href="alias.php?domain='.$domain->getName().'&view='.$alias. '">' .$alias. '</a></td>';
                print '<td><a href="alias.php?domain='.$domain->getName().'&del=' .$alias. '"><span class="glyphicon glyphicon-trash"></span></a></td></tr>';
            }
        }
    ?>

</table>
</form>
</div>

<?php include("inc/fin.php"); ?>

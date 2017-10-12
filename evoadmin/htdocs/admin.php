<?php

/**
 * Listing of all account/aliases
 *
 * Copyright (c) 2004-2005 Evolix - Tous droits reserves
 * $Id: admin.php,v 1.13 2009-09-02 17:22:13 gcolpart Exp $
 *
 * @author Gregory Colpart <reg@evolix.fr>
 * @version 1.0
 */

/*
 * Functions
 */

/**
 * Show account/alias details
 * @param string $compte
 * @param string $type
 * @return NULL
 */
function show_my_details($name,$type) {

    print '<tr><td style="text-align:left;"><a href="' .$type. '.php?view='
        .$name. '">' .$name. '</a></td>';

    if ( $type == 'compte' ) {
        print '<td>' .getquota($name,'user'). '</td>';
    }

    print '<td>';
    print '<a href="' .$type. '.php?del=' .$name. '">
        <span class="glyphicon glyphicon-trash"></span></a>';
    print '</td></tr>';
}


/**
 * Path
 */
define('EVOADMIN_BASE','./');

//recuperer la session en cours
session_name('EVOADMIN_SESS');
session_start();

// TODO : restrictions if non superadmin

if (isset($_SESSION['login'])) {

    /**
     * Requires
     */
    require_once EVOADMIN_BASE . 'lib/common.php';

    include EVOADMIN_BASE . 'inc/haut.php';

    $login = $_SESSION['login'];

    if (isset($_GET['domain'])) {
        // TODO : verifier si le domaine existe !!
        $_SESSION['domain'] = Html::clean($_GET['domain']);
    }

    // TODO : verifier que le domaine est actif
    // et que les droits sont corrects
    $domain = $_SESSION['domain'];

    // RDN for all LDAP search
    if (! $conf['domaines']['onlyone'])  {

        // compatibilite anciens schemas
        if ($conf['evoadmin']['version'] <= 2) {
            $rdn= "domain=" .$domain. "," .LDAP_BASE;
        } else {
            $rdn= "cn=" .$domain. "," .LDAP_BASE;
        }

    } else {
        $rdn= "ou=people," .LDAP_BASE;
    }
    $_SESSION['rdn'] = $rdn;

    include EVOADMIN_BASE . 'inc/debut.php';

    // tableau contenant tous les comptes
    $comptes = array();
    // tableau contenant tous les alias
    $aliases = array();

    $ldapconn = Ldap::lda_connect(LDAP_ADMIN_DN,LDAP_ADMIN_PASS);

    $filter="(objectClass=mailAccount)";
    $sr=ldap_search($ldapconn, $rdn, $filter);
    $info = ldap_get_entries($ldapconn, $sr);

    // We use uid attribute for account
    for ($i=0;$i<$info["count"];$i++) {
        array_push($comptes,$info[$i]["uid"][0]);
    }

    // We use cn attribute for alias
    $filter="(objectClass=mailAlias)";
    // compatibilite anciens schemas
    if ($conf['evoadmin']['version'] == 1) {
	    $filter="(&(objectClass=mailAlias)(onlyAlias=TRUE))";
    }
    $sr=ldap_search($ldapconn, $rdn, $filter);
    $info = ldap_get_entries($ldapconn, $sr);

    for ($i=0;$i<$info["count"];$i++) {
        array_push($aliases,$info[$i]["cn"][0]);
    }

    ldap_unbind($ldapconn);

    //tri alphanumeriques des tableaux
    sort($comptes);
    sort($aliases);
?>
       <div class="container">
	   	<div class="text-center">
        <a href="compte.php"><button class="btn btn-primary">Ajouter un nouveau compte</button></a>&nbsp;&nbsp;&nbsp;

        <?php
            // only for mail mode
            if (($conf['admin']['what'] == 1) || ($conf['admin']['what'] == 3)) {

            $viewonly1= ( (isset($_POST['viewonly'])) && ($_POST['viewonly']==2) ) ? "" : "selected='selected'";
            $viewonly2= ( (isset($_POST['viewonly'])) && ($_POST['viewonly']==2) ) ? "selected='selected'" : "";
        ?>

            <a href="alias.php"><button class="btn btn-primary">Ajouter un nouvel alias/groupe de diffusion</button></a>
	   	</div>
            <hr>
            <form class='center' action='<?php print $_SERVER['PHP_SELF'];?>'
                method='POST' name='listing'>
				<div class="form-group">
	                <select class="form-control" name='viewonly' onchange="document.listing.submit()">
		                <option value='1' <?php print $viewonly1; ?>>Liste des comptes</option>
		                <option value='2' <?php print $viewonly2; ?>>Liste des alias/groupe de diffusion</option>
					</select>
				</div>
            </form>

        <?php
            }

            if ( (!isset($_POST['viewonly'])) || ($_POST['viewonly']==1) ) {

        ?>

<!-- 			<h2>Liste des comptes :</h2><hr> -->

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
                foreach ($comptes as $compte) {
                show_my_details($compte,'compte');
                }
      
                print "</tbody></table>";

           } elseif ( (isset($_POST['viewonly'])) && ($_POST['viewonly']==2) ) {
    
        ?>

<!--             <h2>Liste des alias/groupe de diffusion&nbsp;:</h2> -->
    
            <table class="table table-striped table-condensed">
	            <thead>
		            <tr>
		            <th><strong>Nom de l'alias/groupe de diffusion</strong></th>
		            <th width="50px">Suppr</th>
		            </tr>
				</thead>
				<tbody>


            <?php

                foreach ($aliases as $alias) {
                show_my_details($alias,'alias');
                }
            }
        ?>

    </table>
    </div>

<?php

} else { //if (isset($_SESSION['login']))
    header("location: auth.php\n\n");
    exit(0);
}

include EVOADMIN_BASE . 'inc/fin.php';

?>

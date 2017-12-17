<?php

// Load config and autoload class
require_once("lib/config.php");

// Force authentication on this page
require_once("lib/auth.php");

require_once('lib/common.php');

include('inc/haut.php');
include('inc/debut.php');

if (!empty($_POST['cn'])) {
    $cn = (!empty($_GET['alias'])) ? $alias->getName() : Html::clean(Html::purgeaccents(utf8_decode($_POST['cn']))); 
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

    print '<center>';

    try {
        if (!empty($_GET['alias'])) {
            print "<div class=\"alert alert-info\" role=\"alert\">Modification en cours...</div>";
            $alias->update($actif,$mailaccept,$maildrop);
            header('Location: alias.php?domain='.$domain->getName().'&alias='.$alias->getName());
        } else {
            print "<div class=\"alert alert-info\" role=\"alert\">Ajout en cours...</div>";
            $domain->addAlias($cn,$actif,$mailaccept,$maildrop);
            print "<div class=\"alert alert-succes\" role=\"alert\">Ajout effectu&eacute;.</div>";
            print '<a href="alias.php?domain='.$domain->getName().'&alias='.$cn.'"><button class="btn btn-primary">Voir l\'alias cr&eacute;&eacute;</button></a>';
        }
    } catch (Exception $e) {
       print '<div class="alert alert-danger" role="alert">'.$e->getMessage().'</div>'; 
    }

    print "</center>";

}

if (isset($_GET['alias'])) {
    print "<center>";
    print '<h4>Modification de l\'alias '.$alias->getName().'</h4>';

    print '<form name="add" action="alias.php?domain='.$domain->getName().'&alias='.$alias->getName().'" method="post">';

    print '<input type="hidden" name="cn" value="'.$alias->getName().'"/>';

    print "<table>";

    print "<tr><td colspan='2'>";
    print "<p class='italic'>Ajoutez/modifiez/supprimez les mails accept&eacute;s en entr&eacute;e).<br />
        Un minimum d'un mail est requis. M&ecirc;mes instructions<br />
        pour les redirections (compte(s) dans le(s)quel(s) est/sont d&eacute;livr&eacute;(s) les mails).
        </p>";
    print "</td></tr>";

    foreach($alias->getAliases() as $mailaccept) {
        print "<tr><td align='right'>Mail accept&eacute; en entr&eacute;e :</td>
            <td align='left'><input type='text' name='mailaccept[]' size='30' value='".$mailaccept."' />";
            if (!$conf['domaines']['onlyone']) {
                print "@" .$domain->getName();
            }

            print "</td></tr>";
    }

    print "<tr><td align='right'>Cr&eacute;ation d'un nouveau mail accept&eacute; en entr&eacute;e :</td>
        <td align='left'><input type='text' name='mailaccept[]'
        size='30'/>";
    if (!$conf['domaines']['onlyone']) {
        print "@" .$domain->getName();
    }
    print "</td></tr>";

    print "<tr><td align='right'>Cr&eacute;ation d'un nouveau mail accept&eacute; en entr&eacute;e :</td>
        <td align='left'><input type='text' name='mailaccept[]'
        size='30'/>";
    if (!$conf['domaines']['onlyone']) {
        print "@" .$domain->getName();
    }
    print "</td></tr>";

    print "<tr><td align='right'>Cr&eacute;ation d'un nouveau mail accept&eacute; en entr&eacute;e :</td>
        <td align='left'><input type='text' name='mailaccept[]'
        size='30'/>";
    if (!$conf['domaines']['onlyone']) {
        print "@" .$domain->getName();
    }
    print "</td></tr>";

    foreach($alias->getRedirections() as $red) {
        print "<tr><td align='right'>Mails entrants redirig&eacute;s vers :</td>
            <td align='left'><input type='text' name='maildrop[]'
            size='30' value='" .$red. "'/>
            </td></tr>";
    }

    print "<tr><td align='right'>Nouvelle redirection vers :</td>
        <td align='left'><input type='text' name='maildrop[]'
        size='30'' /></td></tr>";
    print "<tr><td align='right'>Nouvelle redirection vers :</td>
        <td align='left'><input type='text' name='maildrop[]'
        size='30'/></td></tr>";
    print "<tr><td align='right'>Nouvelle redirection vers :</td>
        <td align='left'><input type='text' name='maildrop[]'
        size='30'/></td></tr>";

    print "<tr><td colspan='2'>";
    print "<p class='italic'>Activer/d&eacute;sactiver l'alias</p>";
    print "</td></tr>";

    $isactive= ($alias->isActive()) ? 'checked="checked"' : '';
    print "<tr><td align='right'>Alias actif :</td>
        <td align='left'><input type='checkbox' name='isactive'
        $isactive /></td></tr>";

    print "<tr><td>&nbsp,</td><td align='left'>";
    print "<p><input type='submit' class='button' 
        value='Valider' name='valider'/></p>";
    print "</td></tr>";
        
    print "</table>";
    print '</form>';
} else {

?>

<center>
    
<h4>Ajout d'un alias</h4>

<form name="add" action="alias.php?domain=<?php print $domain->getname(); ?>" method="post">

<p class="italic">Remplissez lez champs.</p>

<table>

<tr><td align="right">Nom (unique) de l'alias :</td>
<td align="left"><input type='text' name='cn'/></td></tr>

<tr><td align="right">Alias :</td>
<td align="left"><input type='text' name='mailaccept[]'/>
<?php
    if (!$conf['domaines']['onlyone']) {
       print "@" .$domain->getName();
    }
?>
</td></tr>

<tr><td align="right">Alias :</td>
<td align="left"><input type='text' name='mailaccept[]'/>
<?php
    if (!$conf['domaines']['onlyone']) {
       print "@" .$domain->getName();
    }
?>
</td></tr>

<tr><td align="right">Alias :</td>
<td align="left"><input type='text' name='mailaccept[]'/>
<?php
    if (!$conf['domaines']['onlyone']) {
       print "@" .$domain->getName();
    }
?>
</td></tr>

<tr><td align="right">Alias :</td>
<td align="left"><input type='text' name='mailaccept[]'/>
<?php
    if (!$conf['domaines']['onlyone']) {
       print "@" .$domain->getName();
    }
?>
</td></tr>

<tr><td align="right">Alias :</td>
<td align="left"><input type='text' name='mailaccept[]'/>
<?php
    if (!$conf['domaines']['onlyone']) {
       print "@" .$domain->getName();
    }
?>
</td></tr>

<tr><td align="right">Redirection :</td>
<td align="left"><input type='text' name='maildrop[]'/></td></tr>

<tr><td align="right">Redirection :</td>
<td align="left"><input type='text' name='maildrop[]'/></td></tr>

<tr><td align="right">Redirection :</td>
<td align="left"><input type='text' name='maildrop[]'/></td></tr>

<tr><td align="right">Redirection :</td>
<td align="left"><input type='text' name='maildrop[]'/></td></tr>

<tr><td align="right">Redirection :</td>
<td align="left"><input type='text' name='maildrop[]'/></td></tr>

<tr><td colspan="2">
<p class="italic">Activer/d&eacute;sactiver l'alias</p>
</td></tr>

<tr><td align="right">Alias actif :</td>
<td align="left"><input type='checkbox' name='isactive' checked /></td></tr>


<tr><td>&nbsp;</td><td align="left">
<p><input type="submit" class="button" value="Valider" name="valider" /></p>
</td></tr>

</table>
</form>

</center>

<?php } include('inc/fin.php'); ?>

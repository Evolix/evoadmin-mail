<?php

    print '<a href="superadmin.php"><img src="inc/home.png" /></a>';
    //print '<a href="admin.php"><img src="inc/home.png" /></a>';
?>

<a href="help.php"><img src="inc/help.png" /></a>

<a href="<?php print $conf['url']['webroot']; ?>">
<img src="inc/exit.png" /></a>


<?php

print "<p class='login'>Vous &ecirc;tes <b>$login</b>.<br>";

if (isset($_SESSION['domain']))
{
        print "Vous administrez le domaine <a href='admin.php'>"
            .$_SESSION['domain']. "</a></h5>";
        
}
print '</p>';
?>


<nav class="navbar navbar-default navbar-fixed-top">
    <div id="top" class="container">
        <div class="navbar-brand logo">
            <a href="https://evolix.com/" target="_blank">
                <img src="img/logo.png" alt="Evolix" class="img-responsive"/>
            </a>
        </div>
        <ul class="nav navbar-nav">
            <li><a href="superadmin.php">Accueil</a></li>
            <li><a href="help.php">Aide</a></li>
            <?php
                if (superadmin($login)) { echo '<li><a href="domaine.php">Ajout Domaine</a></li>'; }
            ?>
            <li><a href="<?php print $conf['url']['webroot']; ?>">Déconnexion</a></li>
        </up>
        <p class="navbar-text navbar-right">
        <?php
            print "<em>$login</em>";
            if (isset($_SESSION['domain'])) {
                print " - Domaine : <a href='admin.php'>".$_SESSION['domain']. "</a>";
            }
        ?>
        </p>
    </div>
</nav>










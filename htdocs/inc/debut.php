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
                if ($server->isSuperAdmin()) { echo '<li><a href="domaine.php">Ajout Domaine</a></li>'; }
            ?>
            <li><a href="auth.php">Déconnexion</a></li>
        </up>
        <p class="navbar-text navbar-right">
        <?php
            print "<em>".$server->getLogin()."</em>";
            if (!empty($domain)) {
                print ' - Domaine : <a href="admin.php?domain='.$domain->getName().'">'.$domain->getName().'</a>';
            }
        ?>
        </p>
    </div>
</nav>










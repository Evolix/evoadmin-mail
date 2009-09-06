<?php

// classic mail notification 
// (you can customize it in hook.php)
function mailnotify($info,$domain,$pass) {

    global $conf;

    $gecos = $info["cn"];
    $unixlogin = $info["uid"];

    //mail de notification
    $sujet = "Creation du compte $unixlogin" ;
    $to = $conf['admin']['mail'];

    $entete  = "From: ".$conf['admin']['mail']."\n";
    $entete .= "MIME-Version: 1.0\n";
    $entete .= "Content-type: text/plain; charset=utf-8\n";
    $entete .= "Content-Transfer-Encoding: quoted-printable\n";

    $contenu  = "Bonjour $gecos,\n\n";
    $contenu .= "Un nouveau compte vient d'être créé pour vous.\n";
    $contenu .= "Votre identifiant est : $unixlogin\n";
    $contenu .= "Votre mot de passe : " .$pass. "\n\n";
    $contenu .= "Cordialement,\n";
    $contenu .= "--\nL'équipe informatique";
    mail($to,$sujet,$contenu,$entete);
}

// classic domain notification 
// (you can customize it in hook.php)
function domainnotify($domain) {

    global $conf;

    //mail de notification
    $sujet = "Creation du domaine $domain" ;
    $to = $conf['admin']['mail'];

    $entete  = "From: ".$conf['admin']['mail']."\n";
    $entete .= "MIME-Version: 1.0\n";
    $entete .= "Content-type: text/plain; charset=utf-8\n";
    $entete .= "Content-Transfer-Encoding: quoted-printable\n";

    $contenu  = "Bonjour,\n\n";
    $contenu .= "Un nouveau domaine vient d'être créé : $domain\n";
    $contenu .= "Assurez vous bien que la configuration DNS et MX\n";
    $contenu .= "soit bien en place.\n\n";
    $contenu .= "Cordialement,\n";
    $contenu .= "--\nL'équipe informatique";
    mail($to,$sujet,$contenu,$entete);
}



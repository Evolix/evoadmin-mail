#!/usr/bin/perl -w

# Script de creation de compte mail/LDAP (cas virtuel)
# Copyright (c) 2004-2009 Evolix - Tous droits reserves

use strict;
use warnings;
use diagnostics;

# Doc Net::LDAP
# http://ldap.perl.org/
# http://search.cpan.org/~gbarr/perl-ldap-0.33/lib/Net/LDAP.pod
# http://search.cpan.org/~gbarr/perl-ldap/lib/Net/LDAP/Examples.pod
# http://search.cpan.org/~gbarr/perl-ldap/lib/Net/LDAP/FAQ.pod
# http://search.cpan.org/~gbarr/perl-ldap-0.33/lib/Net/LDAP/Extension/SetPassword.pm
#
# Doc Quota
# http://search.cpan.org/~tomzo/Quota-1.5.1/Quota.pm

use Net::LDAP;	   # libnet-ldap-perl  debian package
use Getopt::Std;
use Term::ReadKey; # libterm-readkey-perl debian package
use MIME::Base64;  #
#use Digest::MD5;   # libmd5-perl debian package
use Digest::SHA1;   # libdigest-sha1-perl debian package
use MIME::Lite;    # libmime-lite-perl debian package
use Quota;	   # libquota-perl debian package

our $dn='dc=example,dc=com';
our $host='127.0.0.1';
our $binddn='cn=perl,ou=ldapuser,dc=example,dc=com';
our $password='XXX';

our $adminmail='admin@example.com';

our $file='/var/log/evolix.log';
my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst)=localtime(time);
our $date= sprintf("%4d-%02d-%02d %02d:%02d ",$year+1900,$mon+1,$mday,$hour,$min);

sub usage()
{
print STDERR << "EOF";

    usage: $0 [-hud]

    -h        : aide
    -u        : ajoute un compte
    -d        : ajoute un domaine
    -a        : ajoute un alias
    -p        : modifie un mot de passe

EOF
exit;
}

sub add()
{
    printf("Entrez le mail a creer : ");
    my $mail = <STDIN>;
    chomp $mail;
    # TODO : Voir si le mail est correct... 

    printf("Entrez le mot de passe (vide pour aleatoire) : ");
    ReadMode('noecho');  
    my $pass = ReadLine(0);
    chomp $pass;
    ReadMode('normal');
    printf("\n");

    # Generation aleatoire
    if($pass eq "") {
        $pass = `apg -n1 -E oOlL10\&\\\/`;
        chomp $pass;
	print "Mot de passe pseudo-aleatoire genere : $pass\n";
    }

    my ($login,$domain) = split(/@/,$mail);

    my $ldap = Net::LDAP->new($host) or die "$@";
    my $mesg = $ldap->bind($binddn,password => $password, version => 3);
   
    my $result = $ldap->search(
        base => $dn,
	filter => "(uid=$mail)",
	attrs => "mail"
	);
    $result->code && die $result->error;
    if ($result->entries) { printf("Erreur, ce compte mail existe deja...\n"); exit; }

    # Voir si le domaine existe deja et recuperer son GID
    my $gid;
    $result = $ldap->search(
        base => $dn,
	filter => "(cn=$domain)",
	attrs => "postfixGID"
	);
    $result->code && die $result->error;
  
    if ($result->count != 1) { printf("Erreur, ce domaine n'existe pas...\n"); exit;
    } else {
	my @entries = $result->entries;
	$gid = $entries[0]->get_value("postfixGID");
    }

    # Recuperer le max(UID) +1
    $result = $ldap->search(
        base => $dn,
        filter => "(objectClass=vMailAccount)",
        attrs => "uidNumber"
        );
    $result->code && die $result->error;

    my $uid = 10000; # Les virtual UIDs commencent a 10000
    my @entries = $result->entries;
    my $entr;
    foreach $entr (@entries) {
        if ( $entr->get_value("uidNumber") > $uid ) {
	    $uid = $entr->get_value("uidNumber");
	}
    }
    $uid++;

    # Ajouter l'enregistrement
    my $ctx = Digest::SHA1->new;
    $ctx->add($pass);
    $ctx->add('salt');
    my $hashedPasswd = '{SSHA}' . encode_base64($ctx->digest . 'salt' ,'');
    $result = $ldap->add( 'uid='. $mail .',cn=' . $domain .','. $dn ,
    attr => [	
         'mail' =>  $mail,
         'cn' =>  $mail,
	 'mailacceptinggeneralid' => $mail,
	 'objectclass' => ['organizationalRole','posixAccount','mailAccount'],
	 'uidNumber' => $uid,
	 'gidNumber' => $gid,
	 'userPassword' => $hashedPasswd, 
	 'isActive'   => 'TRUE',
	 'courierActive'   => 'TRUE',
	 'accountActive'   => 'TRUE',
	 'webmailActive'   => 'TRUE',
	 'authsmtpActive'   => 'FALSE',
	 'homeDirectory' => "/home/$domain/$login/",
	 'amavisSpamTagLevel' => '-1999.0',
	 'amavisSpamTag2Level' => '6.3'
	     ]
	  );

    $mesg->code && die $mesg->error;
    $ldap->unbind;

    # HOMEDIR (obsolete ?)
    #mkdir "/home/vmail/$domain/$login/",0700 ;
    #chown  $uid,$gid,"/home/vmail/$domain/$login/"; 

    # mail 
    my $msg = MIME::Lite->new(
            From    => $adminmail,
            To      => $mail,
            CC      => '',
            Subject => "Creation du compte",
            Type    => 'TEXT',
            Data    => "Ceci est un mail d'initialisation de votre compte."
	  );
   
    MIME::Lite->send('smtp', "localhost", Timeout=>60);
    $msg -> send;

    # QUOTA
    Quota::setqlim(Quota::getqcarg("/home"), $uid, 102400, 153600, 0, 0, 1, 0);

    # HTML > PS > PDF
    my $html;
    open(FILE,">param-mail.html") or die("erreur, ouverture fichier html");
    print FILE <<EOF;

<html>
<title>Paramètres hébergement mail</title>
</head>
<body>

<img src="logo_care_5_ecran.png">

<center><h1>Paramètres hébergement mail</h1></center>

<h2>Vos paramètres</h2>

<p>Voici vos paramètres à retenir :</p>

<center>
<table border="1">
<tr><td align="right">Identifiant :</td><td><strong>$mail</strong></td></tr>
<tr><td align="right">Mot de passe :</td><td><strong>$pass</strong></td></tr>
</table>
</center>

<p>Votre quota actuel est de 100 Mo.
Votre boîte aux lettres est dotée d'une protection Antivirus et Antispam.</p>

<h2>Détails pour l'utilisation</h2>

<p>Vous pouvez envoyer et consulter vos mails par un webmail securisé à l'adresse :</p>

<strong>http://webmail.example.com/</strong>

<p>Vous pouvez également consulter votre messagerie avec votre logiciel
habituel de messagerie (Microsoft Outlook, Mozilla Thunderbird, etc.)
avec le protocole POP ou IMAP en précisant comme adresse de serveur :</p>
<strong>mail.example.com</strong>

<p>Vous pouvez aussi utiliser un serveur SMTP
<u>authentifié</u> en précisant l'adresse du serveur :</p>
<strong>mail.example.com</strong>

<p>Notez bien qu'il faut utiliser l'authentification SMTP sinon cela ne
fonctionnera pas (il faut souvent cocher une case du type "mon serveur
recquiert une authentification").</p>

<p>Enfin, notez qu'il est fortement recommandé d'utiliser une connexion
sécurisée pour tous les protocoles cités auparavant (POP, IMAP ou encore SMTP).</p>

<h2>Support</h2>

<p>Pour toute précision vous pouvez consulter site :</p>
<strong>http://www.evolix.fr/serv/hebergement/mail.html</strong>

<p>ou nous contacter à l'adresse :</p>
<strong>$adminmail</strong>

</body>
</html>
EOF

    close(FILE);
    system("cd /usr/share/scripts && html2ps param-mail.html > param-mail.ps && ps2pdf param-mail.ps");

    # mail 
    $msg = MIME::Lite->new(
         From    => $adminmail,
	 To      => $adminmail,
	 Subject => "Creation du compte $mail",
	 Type    => 'multipart/mixed',
    );

    my $data;
    $data  = "Bonjour,\n\nVeuillez trouvez vos parametres mail en piece jointe (PDF).\n\n";
    $data .= "Cordialement,\n--\nEquipe Informatique <$adminmail>\n";

    $msg->attach(Type     =>'TEXT',
                 Data    => $data
                 );
    $msg->attach(Type     => 'application/pdf',
                 Path    => '/usr/share/scripts/param-mail.pdf',
                 Filename => 'param-mail.pdf',
                 Disposition => 'attachment'
                 );

    MIME::Lite->send('smtp', "localhost", Timeout=>60);
    $msg -> send;

    printf("Ajout OK\n");

    open F, ">>$file";
    print F ("$date [add.pl] Ajout mail $login\n");
    close F;

}

sub gadd()
{
    printf("Entrez le domaine a creer : ");
            my $domain = <STDIN>;
            chomp $domain;
    # TODO : Voir si le domaine est correct... 

    my $ldap = Net::LDAP->new($host) or die "$@";
    my $mesg = $ldap->bind($binddn,password => $password, version => 3);

    my $result = $ldap->search(
        base => $dn,
        filter => "(cn=$domain)",
        attrs => "gidNumber"
    );
    $result->code && die $result->error;
    if ($result->entries) { printf("Erreur, ce domaine existe deja...\n"); exit; }

    # Recuperer le max(GID) +1
    $result = $ldap->search(
        base => $dn,
        filter => "(objectClass=postfixDomain)",
        attrs => "gidNumber"
    );
    $result->code && die $result->error;

    my $gid = 10000; # Les virtual GIDs commencent a 10000
    my @entries = $result->entries;
    my $entr;
    foreach $entr (@entries) {
        if ( $entr->get_value("gidNumber") > $gid ) {
	    $gid = $entr->get_value("gidNumber");
	}
    }
    $gid++;

   # Ajouter l'enregistrement
   $result = $ldap->add( 'cn='. $domain .','. $dn ,
    attr => [
                'cn' =>  $domain,
                'objectclass' => ['postfixDomain','posixGroup'],
                'gidNumber' => $gid,
                'isActive'   => 'TRUE',
                'postfixTransport' => 'virtual:'
             ]
          );

    $mesg->code && die $mesg->error;
    $ldap->unbind;

    # HOMEDIR
    mkdir "/home/vmail/$domain/",0770 ;
    chown  127,$gid,"/home/vmail/$domain/";
    chmod  0770,"/home/vmail/$domain/";
  
    # QUOTA
    Quota::setqlim(Quota::getqcarg("/home"), $gid, 1024000, 1536000, 0, 0, 1, 1);

    printf("Ajout OK\n");
       
    open F, ">>$file";
    print F ("$date [add.pl] Ajout domaine $domain\n");
    close F;
}

# modification d'un password
sub passwd() {

    printf("Entrez le compte pour réinitialiser le mot de passe : ");
    my $login = <STDIN>;
    chomp $login;
    my $uid;
    my $uidn;

    my $ldap = Net::LDAP->new($host) or die "$@";
    my $mesg = $ldap->bind($binddn,password => $password, version => 3);

    my $result = $ldap->search(
                        base => $dn,
                        filter => "(uid=$login)",
                        );
    $result->code && die $result->error;

    if ($result->count != 1) { printf("Erreur, ce mail n'existe pas...\n"); exit;
    } else {
        my @entries = $result->entries;
        $uid = $entries[0]->get_value("uid");
        $uidn = $entries[0]->dn();
    }

    print "Compte " . $uid . " trouvé\n";
    my $actiondn = $uidn;

    print "Entrez le nouveau mot de passe (vide pour aleatoire) : ";
    ReadMode('noecho');
    my $pass = ReadLine(0);
    chomp $pass;
    ReadMode('normal');
    printf("\n");

    # Generation aleatoire
    if($pass eq "") {
        $pass = `apg -n1 -E oOlL10\&\\\/`;
        chomp $pass;
	print "Mot de passe pseudo-aleatoire genere : $pass\n";
    }

    # set SSHA1 LDAP passwd with http://www.taclug.org/documents/openldap_presentation.html
    my $ctx = Digest::SHA1->new;
    $ctx->add($pass);
    $ctx->add('salt');
    my $hashedPasswd = '{SSHA}' . encode_base64($ctx->digest . 'salt' ,'');

    $ldap->modify( $actiondn, replace => { 'userPassword' => $hashedPasswd } );
    print "Réinitialisation password $login OK\n";

    open F, ">>$file";
    print F ("$date [add.pl] Modification passwd sur le mail $login\n");
    close F;

    $ldap->unbind;
}

sub aadd()
{
    printf("Entrez l'alias a creer : ");
    my $alias = <STDIN>;
    chomp $alias;
    # TODO : voir si l'alias est correct...

    my ($login,$domain) = split(/@/,$alias);

    my $ldap = Net::LDAP->new($host) or die "$@";
    my $mesg = $ldap->bind($binddn,password => $password, version => 3);
   
    # Voir si le domaine existe
    my $result = $ldap->search(
        base => $dn,
	filter => "(cn=$domain)",
	attrs => "cn"
	);
    $result->code && die $result->error;
  
    if ($result->count != 1) { printf("Erreur, ce domaine n'existe pas...\n"); exit;}

    our $edn = "cn=$domain," . $dn;

    # Voir si l'alias existe deja
    $result = $ldap->search(
        base => $edn,
	filter => "(cn=$login)",
	attrs => "mail"
	);
    $result->code && die $result->error;
    if ($result->entries) { printf("Erreur, cet alias existe deja...\n"); exit; }

    printf("Entrez vers ou l'alias pointe : ");
    my $drop = <STDIN>;
    chomp $drop;

    $result = $ldap->add( 'cn='. $login .','. $edn ,
      attr => [
                'mailacceptinggeneralid' => $alias,
                'maildrop' =>  $drop,
		'objectclass' => ['mailAlias'],
                'isActive'  => 'TRUE'
              ]
          ) or die "heh : $!";

    $mesg->code && die $mesg->error;
    $ldap->unbind;

    printf("Ajout OK\n");

    open F, ">>$file";
    print F ("$date [add.pl] Ajout alias $alias\n");
    close F;
}

# main() : options possibles

my %options=();
my $opt_string = 'hudpa';
getopts("$opt_string",\%options);

if    ($options{h}) { &usage; }
elsif ($options{u}) { &add; }
elsif ($options{d}) { &gadd; }
elsif ($options{p}) { &passwd; }
elsif ($options{a}) { &aadd; }
else                { &usage; }


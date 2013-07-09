#!/usr/bin/perl -w

# Script de creation de compte mail/LDAP (cas UNIX)
# Copyright (c) 2004-2009 Evolix - Tous droits reserves

use strict;
use warnings;

use Net::LDAP;      # libnet-ldap-perl debian package
use Getopt::Std;
use Term::ReadKey;  # libterm-readkey-perl debian package
use MIME::Base64;   #
use Digest::SHA1;   # libdigest-sha1-perl debian package
use Quota;          # libquota-perl debian package
use Crypt::SmbHash; # libcrypt-smbhash-perl debian package
use MIME::Lite;     # libmime-lite-perl debian package
use Switch;

# Parametres LDAP

our $dn='dc=example,dc=com';
our $host='127.0.0.1';
our $binddn='cn=perl,ou=ldapusers,dc=example,dc=com';
our $password='XXX';

our $adminmail='admin@example.com';

our $file='/var/log/evolix.log';
my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst)=localtime(time);
our $date= sprintf("%4d-%02d-%02d %02d:%02d ",$year+1900,$mon+1,$mday,$hour,$min);

sub usage()
{
print STDERR << "EOF";

    usage: $0 [-hualmcdpx]

    -h        : aide
    -u        : ajoute un compte
    -a        : ajoute un alias
    -l        : liste les comptes
    -m	      : liste les alias
    -c	      : modifier compte
    -d	      : modifier alias
    -p	      : modifier password
    -x        : supprimer un compte

EOF

exit;
}

sub add()
{   
    printf("Entrez le compte a creer (sans partie @...) : ");
    my $login = <STDIN>;
    chomp $login;

    my $ldap = Net::LDAP->new($host) or die "$@";
    my $mesg = $ldap->bind($binddn,password => $password, version => 3);

    our $edn = 'ou=people,' . $dn;

    # Voir si le compte existe deja

    my $result = $ldap->search(
        base => $edn,
        filter => "(uid=$login)",
        attrs => "uid"
        );
    $result->code && die $result->error;
    if ($result->entries) { printf("Erreur, ce compte existe deja...\n"); exit; }

    # TODO : detecter si un @ dans le login
    # TODO : generer un mot de passe
    
    printf("Entrez le mot de passe : ");
    ReadMode('noecho');
    my $pass = ReadLine(0);
    chomp $pass;
    ReadMode('normal');
    printf("\n");

    # Recuperer le max(UID) +1
    $result = $ldap->search(
        base => $dn,
        filter => "(objectClass=posixAccount)",
        attrs => "uidNumber"
        );
    $result->code && die $result->error;

    my $uid = 10000; # Les uidNumber commencent a 10000
    my @entries = $result->entries;
    my $entr;
    foreach $entr (@entries) {
        if ( $entr->get_value("uidNumber") > $uid ) {
            $uid = $entr->get_value("uidNumber");
	}
    }
    $uid++;


    # Ajouter l'enregistrement

    # set SSHA1 LDAP passwd with http://www.taclug.org/documents/openldap_presentation.html
    my $ctx = Digest::SHA1->new;
    $ctx->add($pass);
    $ctx->add('salt');
    my $hashedPasswd = '{SSHA}' . encode_base64($ctx->digest . 'salt' ,'');
 
    $result = $ldap->add( 'uid='. $login .','. $edn ,
       attr => [
                'uid' => $login,
                'sn' =>  $login,
                'cn' =>  $login,
                'objectclass' => ['inetOrgPerson','posixAccount','shadowAccount','mailAccount'],
                'uidNumber' => $uid,
                'gidNumber' => '10000',
                'userPassword' => $hashedPasswd,
		'homeDirectory' => '/home/' . $login,
		'loginShell' => '/bin/bash',
   	        'mailacceptinggeneralid' => $login,
   	        'maildrop'       => $login,
	        'accountActive'  => 'TRUE',
                'authsmtpActive' => 'TRUE',
                'courierActive'  => 'TRUE',
	        'webmailActive'  => 'TRUE'
               ]
          ) or die "heh : $!";

    $mesg->code && die $mesg->error;
    $ldap->unbind;

    # $HOME
    mkdir "/home/$login/",0700 ;
    # TODO : voir pourquoi les drois ne sont pas pris en compte
    chmod 0700,"/home/$login/";
    chown  $uid,10000,"/home/$login/";
    
    # QUOTA
    Quota::setqlim(Quota::getqcarg("/home"), $uid, 1024000, 1536000, 0, 0, 1, 0);

    # INIT MAIL
    my $msg = MIME::Lite->new(
            From    => $adminmail,
            To      => $login,
            CC      => '',
            Subject => "Initialisation du compte",
            Type    => 'TEXT',
            Data    => "Ceci est un mail d'initialisation de votre compte."
          );

    MIME::Lite->send('smtp', "localhost", Timeout=>60);
    $msg -> send;

    printf("Ajout OK\n");

    # on log
    open F, ">>$file";
    print F ("$date [add.pl] Ajout compte $login\n");
    close F;
}

sub aadd()
{
    printf("Entrez l'alias a creer : ");
            my $alias = <STDIN>;
            chomp $alias;

    # TODO : voir si l'alias est correct...

    my $ldap = Net::LDAP->new($host) or die "$@";
    my $mesg = $ldap->bind($binddn,password => $password, version => 3);

    our $edn = 'ou=people,' . $dn;

    # Voir si le compte existe deja

    my $result = $ldap->search(
        base => $edn,
        filter => "(mailacceptinggeneralid=$alias)",
        attrs => "mailacceptinggeneralid"
        );
    $result->code && die $result->error;
    if ($result->entries) { printf("Erreur, cet alias existe deja...\n"); exit; }

    printf("Entrez vers ou l'alias pointe : ");
            my $drop = <STDIN>;
            chomp $drop;

    $result = $ldap->add( 'mailacceptinggeneralid='. $alias .','. $edn ,
      attr => [
                'mailacceptinggeneralid' => $alias,
                'maildrop' =>  $drop,
		'objectclass' => ['mailAlias'],
                'accountActive'  => 'TRUE'
              ]
          ) or die "heh : $!";

    $mesg->code && die $mesg->error;
    $ldap->unbind;

    printf("Ajout OK\n");

    # on log
    open F, ">>$file";
    print F ("$date [add.pl] Ajout alias $alias\n");
    close F;

}

sub liste1()
{

    my $ldap = Net::LDAP->new($host) or die "$@";
    my $mesg = $ldap->bind($binddn,password => $password, version => 3);

    my $result = $ldap->search(
                        base => $dn,
                        filter => "(objectClass=mailAccount)",
                        attrs => "uid"
                        );
    $result->code && die $result->error;

    my @entries = $result->entries;
    my $entr;
    my @liste;
    foreach $entr (@entries) {

                my $mailacc ="";
                foreach my $value ($entr->get_value("mailacceptinggeneralid")) {
                        $mailacc = $value.",".$mailacc;
                        }
                $mailacc =~ s/,$//;

                my $maildrop ="";
                foreach my $value ($entr->get_value("maildrop")) {
                        $maildrop = $value.",".$maildrop;
                        }
                $maildrop =~ s/,$//;

		@liste = ($entr->get_value("uid").":".$mailacc."->".$maildrop,@liste);
    }
	
    $ldap->unbind;

    my @out;
    @out = sort @liste;
    foreach $entr (@out) {
        print $entr."\n";
    }

}

sub liste2()
{
    my $ldap = Net::LDAP->new($host) or die "$@";
    my $mesg = $ldap->bind($binddn,password => $password, version => 3);

    my $result = $ldap->search(
                        base => $dn,
                        filter => "(objectClass=mailAlias)",
                        attrs => "mailacceptinggeneralid"
                        );
    $result->code && die $result->error;

    my @entries = $result->entries;
    my $entr;
    my @liste;
    foreach $entr (@entries) {
		my $maildrop ="";
		foreach my $value ($entr->get_value("maildrop")) {
			$maildrop = $value.",".$maildrop;
			}
		$maildrop =~ s/,$//;

		@liste = ($entr->get_value("mailacceptinggeneralid").":".$maildrop,@liste);
		}
	
    $ldap->unbind;

    my @out;
    @out = sort @liste;
    foreach $entr (@out) {
        print $entr."\n";
    }

}

sub adel() {

    printf("Entrez l'alias a effacer : ");
    my $alias = <STDIN>;
    chomp $alias;

    my $ldap = Net::LDAP->new($host) or die "$@";
    my $mesg = $ldap->bind($binddn,password => $password, version => 3);

    my $dndelete = 'mailacceptinggeneralid=' . $alias .',ou=people,'. $dn;

    $ldap->delete( $dndelete );

    $ldap->unbind;

    printf("Suppression OK\n");

}

# modification d'un compte
sub mod() {

    printf("Entrez le compte a modifier: ");
    my $login = <STDIN>;
    chomp $login;

    my $ldap = Net::LDAP->new($host) or die "$@";
    my $mesg = $ldap->bind($binddn,password => $password, version => 3);

    my $result = $ldap->search(
			base => $dn,
			filter => "(uid=$login)",
			);
    $result->code && die $result->error;

    print "Compte " . $result->entry(0)->get_value("uid") . " trouvé\n\n";

    print "=> Mail(s) accepté(s) pour ce compte :\n";
    foreach my $value ($result->entry(0)->get_value("mailacceptinggeneralid")) {
        print "- " . $value . "\n";
    }

    print "\n";
    print "=> Ce compte est renvoyé vers :\n";

    foreach my $value ($result->entry(0)->get_value("maildrop")) {
	print "- " . $value . "\n";
    }

    print "\n";
    print "Que voulez-vous faire ?\n";
    print "(activer|desactiver|ajouter-mail|suppr-mail|ajouter-redir|suppr-redir)\n";

    my $action = <STDIN>;
    chomp $action;

    my $actiondn = 'uid=' . $login .',ou=people,'. $dn;

    switch ($action) {
		case (/^activer/i) {
			$ldap->modify( $actiondn, replace => { 'AccountActive' => 'TRUE' } );
			print "compte $login activé\n";
			# on log
			open F, ">>$file";
			print F ("$date [add.pl] Activation compte $login\n");
			close F;

		}

		case (/^d.sactiver/i) {
			$ldap->modify( $actiondn, replace => { 'AccountActive' => 'FALSE' } );
			print "compte $login desactivé\n";

			open F, ">>$file";
			print F ("$date [add.pl] Desactivation compte $login\n");
			close F;
		}

		case (/^ajouter.?mail/i) {
			printf("Entrez le mail a ajouter : ");
			my $newmail = <STDIN>;
			chomp $newmail;

			$ldap->modify( $actiondn, add => { 'mailacceptinggeneralid' => $newmail } );
			print "Ajout $newmail OK\n";
				
			open F, ">>$file";
			print F ("$date [add.pl] Ajout mail $newmail sur compte $login\n");
			close F;
		}

		case (/^suppr.?mail/i) {
			printf("Entrez le mail a supprimer : ");
			my $oldmail = <STDIN>;
			chomp $oldmail;

			$ldap->modify( $actiondn, delete => { 'mailacceptinggeneralid' => $oldmail } );
			print "Suppression $oldmail OK\n";

			open F, ">>$file";
			print F ("$date [add.pl] Suppression mail $oldmail sur compte $login\n");
			close F;
		}

		case (/^ajouter.?redir/i) {
			printf("Entrez la redirection a ajouter : ");
			my $newredir = <STDIN>;
			chomp $newredir;

			$ldap->modify( $actiondn, add => { 'maildrop' => $newredir } );
			print "Ajout $newredir OK\n";
			
			open F, ">>$file";
			print F ("$date [add.pl] Ajout redirection $newredir sur compte $login\n");
			close F;
		}

		case (/^suppr.?redir/i) {
			printf("Entrez la redirection a supprimer : ");
			my $oldredir = <STDIN>;
			chomp $oldredir;

			$ldap->modify( $actiondn, delete => { 'maildrop' => $oldredir } );
			print "Suppression $oldredir OK\n";
			
			open F, ">>$file";
			print F ("$date [add.pl] Suppression redirection $oldredir sur compte $login\n");
			close F;
		}

	}

	$ldap->unbind;
}

# modification d'un alias
sub amod() {

    printf("Entrez l'alias a modifier: ");
    my $alias = <STDIN>;
    chomp $alias;

    my $ldap = Net::LDAP->new($host) or die "$@";
    my $mesg = $ldap->bind($binddn,password => $password, version => 3);

    my $result = $ldap->search(
			base => $dn,
			filter => "(&(mailacceptinggeneralid=$alias)(objectClass=mailAlias))",
			);
    $result->code && die $result->error;

    print "Alias " . $result->entry(0)->get_value("mailacceptinggeneralid") . " trouvé\n\n";

    print "=> Mail(s) accepté(s) pour cet alias :\n";
    foreach my $value ($result->entry(0)->get_value("mailacceptinggeneralid")) {
        print "- " . $value . "\n";
    }

    print "\n";
    print "=> Cet alias est renvoyé vers :\n";

    foreach my $value ($result->entry(0)->get_value("maildrop")) {
	print "- " . $value . "\n";
    }

    print "\n";
    print "Que voulez-vous faire ?\n";
    print "(activer|desactiver|suppr|ajouter-mail|suppr-mail|ajouter-redir|suppr-redir)\n";

    my $action = <STDIN>;
    chomp $action;

    my $actiondn = 'mailacceptinggeneralid=' . $alias .',ou=people,'. $dn;

	switch ($action) {
		case (/^activer/i) {
			$ldap->modify( $actiondn, replace => { 'AccountActive' => 'TRUE' } );
			print "Alias $alias activé\n";
			
			open F, ">>$file";
			print F ("$date [add.pl] Activation alias $alias\n");
			close F;
		}

		case (/^d.sactiver/i) {
			$ldap->modify( $actiondn, replace => { 'AccountActive' => 'FALSE' } );
			print "Alias $alias desactivé\n";
			
			open F, ">>$file";
			print F ("$date [add.pl] Desactivation alias $alias\n");
			close F;
		}

		case (/^ajouter.?mail/i) {
			printf("Entrez le mail a ajouter : ");
			my $newmail = <STDIN>;
			chomp $newmail;

			$ldap->modify( $actiondn, add => { 'mailacceptinggeneralid' => $newmail } );
			print "Ajout $newmail OK\n";
			
			open F, ">>$file";
			print F ("$date [add.pl] Ajout mail $newmail pour alias $alias\n");
			close F;
		}

		case (/^suppr.?mail/i) {
			printf("Entrez le mail a supprimer : ");
			my $oldmail = <STDIN>;
			chomp $oldmail;

			$ldap->modify( $actiondn, delete => { 'mailacceptinggeneralid' => $oldmail } );
			print "Suppression $oldmail OK\n";
			
			open F, ">>$file";
			print F ("$date [add.pl] Suppresion mail $oldmail pour alias $alias\n");
			close F;
		}

		case (/^ajouter.?redir/i) {
			printf("Entrez la redirection a ajouter : ");
			my $newredir = <STDIN>;
			chomp $newredir;

			$ldap->modify( $actiondn, add => { 'maildrop' => $newredir } );
			print "Ajout $newredir OK\n";
			
			open F, ">>$file";
			print F ("$date [add.pl] Ajout redirection $newredir pour alias $alias\n");
			close F;
		}

		case (/^suppr.?redir/i) {
			printf("Entrez la redirection a supprimer : ");
			my $oldredir = <STDIN>;
			chomp $oldredir;

			$ldap->modify( $actiondn, delete => { 'maildrop' => $oldredir } );
			print "Suppression $oldredir OK\n";
			
			open F, ">>$file";
			print F ("$date [add.pl] Suppression redirection $oldredir pour alias $alias\n");
			close F;
		}

		case (/^suppr/i) {

			$ldap->delete( $actiondn );
			print "Suppression alias $alias OK\n";
			
			open F, ">>$file";
			print F ("$date [add.pl] Suppression alias $alias\n");
			close F;
		}

	}

	$ldap->unbind;

}

# modification d'un password
sub passwd() {

    printf("Entrez le compte pour réinitialiser le mot de passe : ");
    my $login = <STDIN>;
    chomp $login;

    my $ldap = Net::LDAP->new($host) or die "$@";
    my $mesg = $ldap->bind($binddn,password => $password, version => 3);

    my $result = $ldap->search(
			base => $dn,
			filter => "(uid=$login)",
			);
    $result->code && die $result->error;

    print "Compte " . $result->entry(0)->get_value("uid") . " trouvé\n";

    my $actiondn = 'uid=' . $login .',ou=people,'. $dn;

    print "Entrez le nouveau mot de passe : ";
    ReadMode('noecho');
    my $pass = ReadLine(0);
    chomp $pass;
    ReadMode('normal');
    printf("\n");
	    
    # set SSHA1 LDAP passwd with http://www.taclug.org/documents/openldap_presentation.html
    my $ctx = Digest::SHA1->new;
    $ctx->add($pass);
    $ctx->add('salt');
    my $hashedPasswd = '{SSHA}' . encode_base64($ctx->digest . 'salt' ,'');

    $ldap->modify( $actiondn, replace => { 'userPassword' => $hashedPasswd } );
    print "Réinitialisation password $login OK\n";
				
    open F, ">>$file";
    print F ("$date [add.pl] Modification passwd sur le compte $login\n");
    close F;

    $ldap->unbind;
}


# suppression d'un compte
sub del()
{
    printf("Entrez le compte a supprimer : ");
    my $login = <STDIN>;
    chomp $login;

    # initialisation de la connexion LDAP 
    my $ldap = Net::LDAP->new($host) or die "$@";
    my $mesg = $ldap->bind($binddn,password => $password, version => 3);

    my $result = $ldap->search(
        base => $dn,
        filter => "(uid=$login)",
    );
    $result->code && die $result->error;
    if (! $result->entries) {
        $ldap->unbind;
    printf("Erreur, ce compte n'existe pas...\n"); exit;
    }

    print "Compte " . $result->entry(0)->get_value("uid") . " trouvé\n";

    my $dndelete = 'uid=' . $login .',ou=people,'. $dn;

    $ldap->delete( $dndelete );
    print "Suppression de l'annuaire LDAP OK\n";

    # On vire le $HOME
    my $day = $date;
    $day =~ s/ .*//;
    `mv /home/$login /home/$login.backup$day`;
    print "Suppression du repertoire /home/$login\n";

    open F, ">>$file";
    print F ("$date, suppression compte $login\n");
    close F;

    $ldap->unbind;
}


# main() : options possibles

my %options=();
my $opt_string = 'hualmcdpx';
getopts("$opt_string",\%options);

if    ($options{h}) { &usage; }
elsif ($options{u}) { &add; }
elsif ($options{a}) { &aadd; }
elsif ($options{l}) { &liste1; }
elsif ($options{m}) { &liste2; }
elsif ($options{c}) { &mod; }
elsif ($options{d}) { &amod; }
elsif ($options{p}) { &passwd; }
elsif ($options{x}) { &del; }
else                { &usage; }


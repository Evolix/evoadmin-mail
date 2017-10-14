# Pré-requis

* Apache
* PHP5 ou supérieur avec certains modules (MHASH, etc.)
* Si utilisé avec Samba, besoin du module PEAR Crypt/CHAP
* sudo
* LDAP

php5-mhash

# Instructions d'installation

* Récupérer les sources Git et les rendre accessible via Apache

* Copier config/connect.php et config/conf.php à partir de leur version "-dist"
  et ajuster les paramètres

* Ajouter le schéma suivant à LDAP :
  http://www.gcolpart.com/hacks/evolix.schema

* Si utilisation de "Samba", c'est plus compliqué... le schéma doit être découpé :

  evolix-inetorgperson.schema :

--8<--
attributetype ( 1.3.6.1.4.1.24331.22.1.3 NAME 'isActive'
        DESC 'an account is active or not'
        EQUALITY booleanMatch
        SYNTAX 1.3.6.1.4.1.1466.115.121.1.7 SINGLE-VALUE )

attributetype ( 1.3.6.1.4.1.24331.22.1.8 NAME 'isAdmin'
        DESC 'boolean to verify if entry is admin for entry'
        EQUALITY booleanMatch
        SYNTAX 1.3.6.1.4.1.1466.115.121.1.7 SINGLE-VALUE )
--8<--

  Puis ajouter au schéma "standard" inetorgperson.schema les attributs isActive
  et isAdmin à la classe d'object inetOrgPerson, ce qui doit donner :

--8<--
# inetOrgPerson
# The inetOrgPerson represents people who are associated with an
# organization in some way.  It is a structural class and is derived
# from the organizationalPerson which is defined in X.521 [X521].
objectclass ( 2.16.840.1.113730.3.2.2
    NAME 'inetOrgPerson'
    DESC 'RFC2798: Internet Organizational Person'
    SUP organizationalPerson
    STRUCTURAL
    MAY (
        audio $ businessCategory $ carLicense $ departmentNumber $
        displayName $ employeeNumber $ employeeType $ givenName $
        homePhone $ homePostalAddress $ initials $ jpegPhoto $
        labeledURI $ mail $ manager $ mobile $ o $ pager $
        photo $ roomNumber $ secretary $ uid $ userCertificate $
        x500uniqueIdentifier $ preferredLanguage $
        userSMIMECertificate $ userPKCS12 $ isActive $
        isAdmin )
    )
--8<--

  Il faut aussi ajouter smbActive au schéma samba.schema :

--8<--
objectclass ( 1.3.6.1.4.1.7165.2.2.6 NAME 'sambaSamAccount' SUP top AUXILIARY
    DESC 'Samba 3.0 Auxilary SAM Account'
    MUST ( uid $ sambaSID )
    MAY  ( cn $ sambaLMPassword $ sambaNTPassword $ sambaPwdLastSet $
           sambaLogonTime $ sambaLogoffTime $ sambaKickoffTime $
           sambaPwdCanChange $ sambaPwdMustChange $ sambaAcctFlags $
               displayName $ sambaHomePath $ sambaHomeDrive $ sambaLogonScript $
           sambaProfilePath $ description $ sambaUserWorkstations $
           sambaPrimaryGroupSID $ sambaDomainName $ sambaMungedDial $
           sambaBadPasswordCount $ sambaBadPasswordTime $
           sambaPasswordHistory $ sambaLogonHours $ smbActive))
--8<--

  Et, enfin, les attributs isActive et isAdmin peuvent être commentés dans le schéma evolix.schema
  et retirer de la classe d'objet mailAccount, ce qui doit donner :

--8<--
# now in evolix-inetorgperson
#attributetype ( 1.3.6.1.4.1.24331.22.1.3 NAME 'isActive'
#        DESC 'an account is active or not'
#        EQUALITY booleanMatch
#        SYNTAX 1.3.6.1.4.1.1466.115.121.1.7 SINGLE-VALUE )

# now in evolix-inetorgperson
#attributetype ( 1.3.6.1.4.1.24331.22.1.8 NAME 'isAdmin'
#        DESC 'boolean to verify if entry is admin for entry'
#        EQUALITY booleanMatch
#        SYNTAX 1.3.6.1.4.1.1466.115.121.1.7 SINGLE-VALUE )

# doit egalement etre posixAccount + { person ou organizationalRole }
objectclass ( 1.3.6.1.4.1.24331.22.2.1 NAME 'mailAccount' SUP top AUXILIARY
        DESC 'LDAP/Unix mail account or virtual account'
        MUST ( uid $ mailacceptinggeneralid )
        MAY ( accountActive $ authsmtpActive $ quota 
            $ courierActive $ webmailActive 
            $ vacationActive $ vacationInfo $ vacationStart $ vacationEnd
            $ vacationForward $ maildrop ) )
--8<--
 
Reste à réordonner l'inclusion des schémas dans le "slapd.conf" :

--8<--
# Schema and objectClass definitions
include         /etc/ldap/schema/core.schema
include         /etc/ldap/schema/cosine.schema
include         /etc/ldap/schema/nis.schema

include         /etc/ldap/schema/evolix-inetorgperson.schema
include         /etc/ldap/schema/inetorgperson.schema

include         /etc/ldap/schema/evolix.schema
include         /etc/ldap/schema/samba.schema
--8<--

* À l'exception du cas "mail virtuel", il est nécessaire de mettre en place un script
  de création :

--8<--
mkdir -p /usr/share/scripts
cp scripts/evoadmin.sh /usr/share/scripts/
chmod +x /usr/share/scripts/evoadmin.sh
--8<--

  Il faut ensuite générer un mot de passe aléatoire à placer
  dans /usr/share/scripts/evoadmin.sh et config/connect.php

  Et, enfin, permettre son lancement via sudo en ajustant le sudoers :

--8<--
User_Alias WWW = www-data
Cmnd_Alias EVOADMIN = /usr/share/scripts/evoadmin.sh
WWW ALL= NOPASSWD: EVOADMIN
--8<--

* Configurer les applications (Postfix, Courier, Samba, etc.) pour utiliser les
  paramètres en place (principalement LDAP).
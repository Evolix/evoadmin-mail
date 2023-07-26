# Install

Evoadmin-mail needs an LDAP server, a Web server and PHP. This documentation explains how to configure OpenLDAP and Apache with mod_php.

Following files extract are [Jinja2](http://jinja.pocoo.org) templates, **{{ varname }}** must be replaced by custom value, e.g.

~~~
ldap_hostname: "mailserver"
ldap_domain: "example.com"
ldap_suffix: "dc=mailserver,dc=example,dc=com"
ldap_admin_password: "password_for_ldap_admin_account"
evoadminmail_admin_password: "password_for_web_interface"
evoadminmail_host: "evoadmin-mail.mailserver.example.com"
~~~

## LDAP

~~~
apt install slapd ldap-utils ldapvi shelldap
~~~

~~~
# /root/evolinux_ldap_config.ldapvi
modify: olcDatabase={1}mdb,cn=config
olcSuffix: {{ ldap_suffix }}
olcRootDN: cn=admin,{{ ldap_suffix }}
olcRootPW: {{ ldap_admin_password }}
olcAccess: {0}to * by dn.exact=gidNumber=0+uidNumber=0,cn=peercred,cn=external,cn=auth manage by * break
olcAccess: {1}to attrs=userPassword by self write by anonymous auth by dn="cn=admin,{{ ldap_suffix }}" write by dn="cn=perl,ou=ldapusers,{{ ldap_suffix }}" write by * none
olcAccess: {2}to attrs=shadowLastChange by self write by dn="cn=admin,{{ ldap_suffix }}" write by dn="cn=perl,ou=ldapusers,{{ ldap_suffix }}" write by * read
olcAccess: {3}to * by self write by dn="cn=admin,{{ ldap_suffix }}" write by dn="cn=perl,ou=ldapusers,{{ ldap_suffix }}" write by * read
~~~

~~~
ldapvi -Y EXTERNAL -h ldapi:// --ldapmodify /root/evolinux_ldap_config.ldapvi
~~~

~~~
# /root/evolinux_ldap_first-entries.ldif
dn: {{ ldap_suffix }}
objectClass: top
objectClass: dcObject
objectClass: organization
o: {{ ldap_domain }}
dc: {{ ldap_hostname }}

dn: cn=admin,{{ ldap_suffix }}
objectClass: simpleSecurityObject
objectClass: organizationalRole
cn: admin
description: LDAP administrator
userPassword: {{ ldap_admin_password }}

dn: ou=ldapusers,{{ ldap_suffix }}
objectClass: top
objectClass: organizationalUnit
ou: ldapusers

dn: cn=perl,ou=ldapusers,{{ ldap_suffix }}
objectClass: simpleSecurityObject
objectClass: organizationalRole
cn: perl
userPassword: {{ ldap_admin_password }}

dn: uid=evoadmin,{{ ldap_suffix }}
uid: evoadmin
cn: Evoadmin ADM
uidNumber: 4242
gidNumber: 4242
homeDirectory: /dev/null
isAdmin: TRUE
mailacceptinggeneralid: evoadmin@{{ ldap_domain }}
objectClass: mailAccount
objectClass: organizationalRole
objectClass: posixAccount
userPassword: {{ evoadminmail_admin_password }}
~~~

~~~
slapadd -l /root/evolinux_ldap_first-entries.ldif
~~~

~~~
# /root/ldap_schema.ldif
dn: cn={4}evolix,cn=schema,cn=config
objectClass: olcSchemaConfig
cn: {4}evolix
olcAttributeTypes: {0}( 1.3.6.1.4.1.24331.22.1.1 NAME 'maildrop' DESC 'mail fo
 rward' SUP mail )
olcAttributeTypes: {1}( 1.3.6.1.4.1.24331.22.1.2 NAME 'mailacceptinggeneralid'
  DESC 'mail alias' SUP mail )
olcAttributeTypes: {2}( 1.3.6.1.4.1.24331.22.1.3 NAME 'isActive' DESC 'boolean
  to verify an global account is active or not' EQUALITY booleanMatch SYNTAX 1
 .3.6.1.4.1.1466.115.121.1.7 SINGLE-VALUE )
olcAttributeTypes: {3}( 1.3.6.1.4.1.24331.22.1.4 NAME 'accountActive' DESC 'bo
 olean to verify if an mail account is active' EQUALITY booleanMatch SYNTAX 1.
 3.6.1.4.1.1466.115.121.1.7 SINGLE-VALUE )
olcAttributeTypes: {4}( 1.3.6.1.4.1.24331.22.1.5 NAME 'authsmtpActive' DESC 'b
 oolean to verify if SMTP-AUTH is enabled for entry' EQUALITY booleanMatch SYN
 TAX 1.3.6.1.4.1.1466.115.121.1.7 SINGLE-VALUE )
olcAttributeTypes: {5}( 1.3.6.1.4.1.24331.22.1.6 NAME 'courierActive' DESC 'bo
 olean to verify if Courier POP/IMAP is enabled for entry' EQUALITY booleanMat
 ch SYNTAX 1.3.6.1.4.1.1466.115.121.1.7 SINGLE-VALUE )
olcAttributeTypes: {6}( 1.3.6.1.4.1.24331.22.1.7 NAME 'webmailActive' DESC 'bo
 olean to verify if webmail is enabled for entry' EQUALITY booleanMatch SYNTAX
  1.3.6.1.4.1.1466.115.121.1.7 SINGLE-VALUE )
olcAttributeTypes: {7}( 1.3.6.1.4.1.24331.22.1.8 NAME 'isAdmin' DESC 'boolean
 to verify if entry is admin for entry' EQUALITY booleanMatch SYNTAX 1.3.6.1.4
 .1.1466.115.121.1.7 SINGLE-VALUE )
olcAttributeTypes: {8}( 1.3.6.1.4.1.24331.22.1.9 NAME 'postfixTransport' DESC
 'transport for Postfix' EQUALITY caseExactIA5Match SYNTAX 1.3.6.1.4.1.1466.11
 5.121.1.26{20} SINGLE-VALUE )
olcAttributeTypes: {9}( 1.3.6.1.4.1.24331.22.1.10 NAME 'domain' DESC 'Postfix
 domain' EQUALITY caseIgnoreIA5Match SUBSTR caseIgnoreIA5SubstringsMatch SYNTA
 X 1.3.6.1.4.1.1466.115.121.1.26 SINGLE-VALUE )
olcAttributeTypes: {10}( 1.3.6.1.4.1.24331.22.1.11 NAME 'quota' DESC 'Courier
 maildir quota' EQUALITY caseIgnoreIA5Match SYNTAX 1.3.6.1.4.1.1466.115.121.1.
 26 SINGLE-VALUE )
olcAttributeTypes: {11}( 1.3.6.1.4.1.24331.22.1.16 NAME 'vacationActive' DESC
 'A flag, for marking the user as being away' EQUALITY booleanMatch SYNTAX 1.3
 .6.1.4.1.1466.115.121.1.7 SINGLE-VALUE )
olcAttributeTypes: {12}( 1.3.6.1.4.1.24331.22.1.17 NAME 'vacationInfo' DESC 'A
 bsentee note to leave behind, while on vacation' EQUALITY octetStringMatch SY
 NTAX 1.3.6.1.4.1.1466.115.121.1.40 SINGLE-VALUE )
olcAttributeTypes: {13}( 1.3.6.1.4.1.24331.22.1.18 NAME 'vacationStart' DESC '
 Beginning of vacation' EQUALITY octetStringMatch SYNTAX 1.3.6.1.4.1.1466.115.
 121.1.40 SINGLE-VALUE )
olcAttributeTypes: {14}( 1.3.6.1.4.1.24331.22.1.19 NAME 'vacationEnd' DESC 'En
 d of vacation' EQUALITY octetStringMatch SYNTAX 1.3.6.1.4.1.1466.115.121.1.40
  SINGLE-VALUE )
olcAttributeTypes: {15}( 1.3.6.1.4.1.24331.22.1.20 NAME 'vacationForward' DESC
  'Where to forward mails to, while on vacation' EQUALITY caseIgnoreIA5Match S
 UBSTR caseIgnoreIA5SubstringsMatch SYNTAX 1.3.6.1.4.1.1466.115.121.1.26{256}
 )
olcAttributeTypes: {16}( 1.3.6.1.4.1.24331.22.1.21 NAME 'smbActive' DESC 'bool
 ean to verify if an Samba account is active' EQUALITY booleanMatch SYNTAX 1.3
 .6.1.4.1.1466.115.121.1.7 SINGLE-VALUE )
olcObjectClasses: {0}( 1.3.6.1.4.1.24331.22.2.1 NAME 'mailAccount' DESC 'LDAP/
 Unix mail account or virtual account' SUP top AUXILIARY MUST ( uid $ mailacce
 ptinggeneralid ) MAY ( accountActive $ authsmtpActive $ quota $ isActive $ co
 urierActive $ webmailActive $ isAdmin $ vacationActive $ vacationInfo $ vacat
 ionStart $ vacationEnd $ vacationForward $ maildrop ) )
olcObjectClasses: {1}( 1.3.6.1.4.1.24331.22.2.2 NAME 'mailAlias' DESC 'Mail al
 iasing/forwarding entry' SUP top STRUCTURAL MUST ( mailacceptinggeneralid $ m
 aildrop ) MAY ( cn $ isActive ) )
olcObjectClasses: {2}( 1.3.6.1.4.1.24331.22.2.4 NAME 'postfixDomain' DESC 'Pos
 tfix domain' SUP posixGroup STRUCTURAL MAY ( postfixTransport $ isActive ) )
~~~

~~~
ldapadd -Y EXTERNAL -H ldapi:/// -f /root/ldap_schema.ldif
~~~

## Apache / PHP

~~~
apt install apache2 libapache2-mod-php php php-cli php-ldap php-log php-twig
~~~

~~~
# /etc/apache2/sites-available/evoadmin-mail.conf
<VirtualHost *:80>
    ServerName {{ evoadminmail_host }}
    Redirect permanent / https://{{ evoadminmail_host }}/
</VirtualHost>

<VirtualHost *:443>

    # FQDN principal
    ServerName {{ evoadminmail_host }}
    #ServerAlias {{ evoadminmail_host }}

    # Repertoire principal
    DocumentRoot /home/evoadmin-mail/www/htdocs/

    # SSL
    SSLEngine on
    SSLCertificateFile    /etc/ssl/certs/{{ evoadminmail_host }}.crt
    SSLCertificateKeyFile /etc/ssl/private/{{ evoadminmail_host }}.key
    SSLProtocol all -SSLv2 -SSLv3

    # Propriete du repertoire
    <Directory /home/evoadmin-mail/www/htdocs/>
        #Options Indexes SymLinksIfOwnerMatch
        Options SymLinksIfOwnerMatch
        AllowOverride AuthConfig Limit FileInfo Indexes
        Require all granted
    </Directory>

    # user - group (thanks to sesse@debian.org)
    AssignUserID www-evoadmin-mail evoadmin-mail

    # LOG
    CustomLog /var/log/apache2/access.log combined
    CustomLog /home/evoadmin-mail/log/access.log combined
    ErrorLog  /home/evoadmin-mail/log/error.log

    # AWSTATS
    SetEnv AWSTATS_FORCE_CONFIG evoadmin-mail

    # REWRITE
    UseCanonicalName On
    RewriteEngine On
    RewriteCond %{HTTP_HOST} !^{{ evoadminmail_host }}$
    RewriteRule ^/(.*) https://%{SERVER_NAME}/$1 [L,R]

    # PHP
    #php_admin_flag engine off
    #AddType text/html .html
    #php_admin_flag display_errors On
    #php_flag short_open_tag On
    #php_flag register_globals On
    #php_admin_value memory_limit 256M
    #php_admin_value max_execution_time 60
    #php_admin_value upload_max_filesize 8M
    #php_admin_flag allow_url_fopen Off
    php_admin_value sendmail_path "/usr/sbin/sendmail -t -i -f www-evoadmin-mail"
    php_admin_value open_basedir "none"
</VirtualHost>
~~~

~~~
a2ensite evoadmin-mail
service apache2 reload
~~~

## Evoadmin-mail

~~~
useradd --create-home evoadmin-mail
git clone https://forge.evolix.org/evoadmin-mail.git /home/evoadmin-mail/www
~~~

~~~
# /home/evoadmin-mail/www/config/config.ini
; The configuration for evoadmin-mail
;
; You need to copy and edit config-sample.ini to config.ini.
; This INI file is loaded by evoadmin-mail and contains the
; following configurations:
;
; * Global settings
; * LDAP settings
;

[global]
name = "Evoadmin Mail";
mail = "evoadmin-mail@example.com"
log_level = error

[ldap]
host = "127.0.0.1"
port = 389
base = "{{ ldap_suffix }}"
admin_dn = "cn=admin,{{ ldap_suffix }}"
admin_pass = "{{ ldap_admin_password }}"
superadmin[] = "evoadmin"
~~~

You can now connect to your Evoadmin-mail with **evoadmin** user and your previously defined password!

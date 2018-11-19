#!/usr/bin/php
<?php

error_reporting(E_ERROR);
require_once("/home/evoadmin-mail/www/htdocs/config/conf.php");
require_once("/home/evoadmin-mail/www/htdocs/config/connect.php");

?>
; The configuration for evoadmin-mail
;
; You need to copy and edit config-sample.ini to config.ini.
; This INI file is loaded by evoadmin-mail and contains the
; following configurations :
;
; * Global settings
; * LDAP settings
;

[global]
name = "<?php echo $conf['html']['title']; ?>";
mail = "<?php echo $conf['admin']['mail']; ?>"
log_level = error

[ldap]
host = "127.0.0.1"
port = 389
base = "<?php echo LDAP_BASE; ?>"
admin_dn = "<?php echo LDAP_ADMIN_DN; ?>"
admin_pass = "<?php echo LDAP_ADMIN_PASS; ?>"
<?php
foreach ($conf['admin']['logins'] as $admin) {
        echo "superadmin[] = \"$admin\"\n";
}
?>

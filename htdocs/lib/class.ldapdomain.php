<?php

class LdapDomain extends LdapServer {
    static $objectClass = array('postfixDomain', 'posixGroup');

    static public function getClassFilter() {
        return '(ObjectClass='.self::$objectClass[0].')';
    }

    protected $domain,$active=false;
    private $quota="0M/0M",$mail_accounts=array(),$mail_alias=array(),$posix_accounts=array(),$smb_accounts=array(),$accounts=array(),$alias=array();

    public function __construct(LdapServer $server, $name) {
        $this->conn = $server->conn;
        $this->login = $server->login;
        $this->superadmin = $server->superadmin;
        $this->dn = $server->dn;

        $this->domain = $name;
        if ($sr = @ldap_search($this->conn, "cn=".$this->domain.",".LDAP_BASE, "(ObjectClass=*)")) {
            $objects = ldap_get_entries($this->conn, $sr);

            foreach($objects as $object) {
                if (!empty($object['objectclass'])) {
                    if (in_array(self::$objectClass[0], $object['objectclass'])) {
                        $this->active = ($object['isactive'][0] == "TRUE") ? true : false;
                    }
                    if (in_array("posixAccount",$object['objectclass'])) {
                        array_push($this->posix_accounts,$object['uid'][0]);
                    }
                    if (in_array(LdapAccount::$objectClass[0], $object['objectclass'])) {
                        array_push($this->mail_accounts,$object['uid'][0]);
                    }
                    if (in_array(LdapAlias::$objectClass[0], $object['objectclass'])) {
                        array_push($this->mail_alias,$object['cn'][0]);
                    }
                    if (in_array("sambaSamAccount",$object['objectclass'])) {
                        array_push($this->smb_accounts,$object['uid'][0]);
                    }
                }
            }
            //$this->quota = getquota($this->domain,'group');
        } else {
            throw new Exception("Ce domaine n'existe pas !");
        }
    }

    public function getAccounts() {
        global $conf;
        if (count($this->accounts) == 0) {
            if (! $conf['domaines']['onlyone']) {
                $rdn = ($conf['evoadmin']['version'] > 2) ? "cn=" .$this->domain. "," .LDAP_BASE : "domain=" .$this->domain. "," .LDAP_BASE;
            } else {
                $rdn = "ou=people," .LDAP_BASE;
            }
            $sr = ldap_search($this->conn, $rdn, LdapAccount::getClassfilter());
            $objects = ldap_get_entries($this->conn, $sr);
            foreach($objects as $object) {
                if(!empty($object["uid"][0])) {
                    $account = new LdapAccount($this, $object["uid"][0]);
                    array_push($this->accounts, $account);
                }
            }
        }
        return $this->accounts;
    }

    public function getAlias() {
        global $conf;
        if (count($this->alias) == 0) {
            if (! $conf['domaines']['onlyone']) {
                $rdn = ($conf['evoadmin']['version'] > 2) ? "cn=" .$this->domain. "," .LDAP_BASE : "domain=" .$this->domain. "," .LDAP_BASE;
            } else {
                $rdn = "ou=people," .LDAP_BASE;
            }
            $sr = ldap_search($this->conn, $rdn, LdapAlias::getClassFilter());
            $objects = ldap_get_entries($this->conn, $sr);
            foreach($objects as $object) {
                if(!empty($object["cn"][0])) {
                    $alias = new LdapAlias($this, $object["cn"][0]);
                    array_push($this->alias, $alias);
                }
            }
        }
        return $this->alias;
    }

    public function addAccount($uid,$name,$password,$active=false,$admin=false,$accountactive=false,$courieractive=false,$webmailactive=false,$authsmtpactive=false,$amavisBypassSpamChecks=false) {
        global $conf;
        if (badname($uid)) {
            throw new Exception("Erreur, <u>$name</u> est un nom invalide.");
        }
        if (Auth::badpassword($password)) {
            throw new Exception("Erreur, mot de passe invalide.");
        }
        $mail = $uid.'@'.$this->getName();
        $password = "{SSHA}".Ldap::ssha($password);
        $info["uid"] = $mail;
        $info["cn"] = $name;
        $info["homeDirectory"] = "/home/vmail/" .$this->getName(). "/" .$uid. "/";
        $info["uidNumber"]= $conf['unix']['uid'];
        $info["gidNumber"]= getgid($this->getName());
        $info["isActive"] = ($active) ? 'TRUE' : 'FALSE';
        $info["isAdmin"] = ($admin) ? 'TRUE' : 'FALSE';
        $info["objectclass"] = LdapAccount::$objectClass;
        $info["maildrop"] = $mail;
        $info["mailacceptinggeneralid"] = $mail;
        $info["accountActive"] = ($accountactive) ? 'TRUE' : 'FALSE';
        $info["courierActive"] = ($courieractive) ? 'TRUE' : 'FALSE';
        $info["webmailActive"] = ($webmailactive) ? 'TRUE' : 'FALSE';
        $info["authsmtpActive"] = ($authsmtpactive) ? 'TRUE' : 'FALSE';
        #$info["amavisBypassSpamChecks"] = ($amavisBypassSpamChecks) ? 'TRUE' : 'FALSE';
        $info["userPassword"] = $password;

        if (@ldap_add($this->conn, "uid=".$mail.",cn=".$this->domain.",".LDAP_BASE, $info)) {
            mail($name, 'Premier message',"Mail d'initialisation du compte.");
            mailnotify($info,$this->getname(),$password);
        } else {
            $error = ldap_error($this->conn);
            throw new Exception("Erreur dans l'ajout du compte : $error");
        }
    }

    public function addAlias($name,$active=false,$mailaccept=array(),$maildrop=array()) {
        $info["cn"] = $name;
        $info["isActive"] = ($active) ? 'TRUE' : 'FALSE';
        $info["objectclass"] = LdapAlias::$objectClass;
        $info["mailacceptinggeneralid"] = $mailaccept;
        $info["maildrop"] = array_filter($maildrop, function($value) {
            return filter_var($value, FILTER_VALIDATE_EMAIL);
        });

        if (!@ldap_add($this->conn, "cn=".$name.",cn=".$this->domain.",".LDAP_BASE, $info)) {
            $error = ldap_error($this->conn);
            throw new Exception("Erreur dans l'ajout de l'alias : $error");
        }
    }

    public function delAccount($uid) {
        $dn = "uid=".$uid.",cn=".$this->domain.",".LDAP_BASE;
        if ($sr = @ldap_search($this->conn, $dn, LdapAccount::getClassFilter())) {
            // Delete account
            if (!ldap_delete($this->conn, $dn)) {
                $error = ldap_error($this->conn);
                throw new Exception("Erreur dans la suppression du compte $uid : $error");
            }
        } else {
            throw new Exception("Ce compte n'existe pas !");
        }
    }
    
    public function delAlias($name) {
        $dn = "cn=".$name.",cn=".$this->domain.",".LDAP_BASE;
        if ($sr = @ldap_search($this->conn, $dn, LdapAlias::getClassFilter())) {
            // Delete alias
            if (!ldap_delete($this->conn, $dn)) {
                $error = ldap_error($this->conn);
                throw new Exception("Erreur dans la suppression de l'alias $name : $error");
            }
        } else {
            throw new Exception("Cet alias n'existe pas !");
        }
    }

    public function update($active=false) {
        $info["isActive"] = ($active) ? 'TRUE' : 'FALSE';
        if (!ldap_mod_replace($this->conn,  "cn=".$this->getName().",".LDAP_BASE, $info)) {
            $error = ldap_error($this->conn);
            throw new Exception("Erreur pendant la modification du domaine : $error");
        }
    }

    public function getName() {
        return $this->domain;
    }

    public function isActive() {
        return $this->active;
    }

    public function getNbAccounts() {
        return count($this->posix_accounts)+count($this->mail_alias);
    }

    public function getNbMailAccounts() {
        return count($this->mail_accounts);
    }

    public function getNbSmbAccounts() {
        return count($this->smb_accounts);
    }

    public function getNbMailAlias() {
        return count($this->mail_alias);
    }

    public function getQuota() {
        return $this->quota;
    }

    public function getMailAccounts() {
        return $this->mail_accounts;
    }

    public function getMailAlias() {
        return $this->mail_alias;
    }

    public function __destruct() {
        return true;
    }
}

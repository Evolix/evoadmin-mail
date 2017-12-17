<?php

class LdapAccount extends LdapDomain {
    static $objectClass = array('mailAccount', 'posixAccount', 'organizationalRole');
    static $dn='uid';

    protected $domain,$uid,$name,$active=false,$admin=false,$courier=false,$authsmtp=false;
    private $aliases=array(),$redirections=array();

    public function __construct(LdapDomain $domain, $uid) {
        $this->domain = $domain;
        $this->conn = $this->domain->server->getConn();

        $this->uid = $uid;
        if ($sr = @ldap_search($this->conn, self::getBaseDN($this->domain, $uid), self::getClassFilter())) {
            $objects = ldap_get_entries($this->conn, $sr);
            $object = $objects[0];
            $this->name = $object['cn'][0];
            $this->active = ($object['isactive'][0] == 'TRUE') ? true : false;
            $this->admin = ($object['isadmin'][0] == 'TRUE') ? true : false;
            $this->courier = ($object['courieractive'][0] == 'TRUE') ? true : false;
            $this->authsmtp = ($object['authsmtpactive'][0] == 'TRUE') ? true : false;
            //$this->quota = getquota($this->domain->getName(),'user');
            $this->aliases = array_filter($object['mailacceptinggeneralid'], "is_string");
            $this->redirections = array_filter($object['maildrop'], "is_string");
        } else {
            throw new Exception("Ce compte n'existe pas !");
        }
    }

    public function update($name=NULL,$password=NULL,$active=NULL,$admin=NULL,$accountactive=NULL,$courieractive=NULL,$webmailactive=NULL,$authsmtpactive=NULL,$amavisBypassSpamChecks=NULL) {
        $info["cn"] = (!empty($name)) ? $name : $this->name;
        if (!empty($password)) {
            $info["userPassword"] = $password;
        }
        $info["isActive"] = ($active) ? 'TRUE' : 'FALSE';
        $info["isAdmin"] = ($admin) ? 'TRUE' : 'FALSE';
        $info["accountActive"] = ($accountactive) ? 'TRUE' : 'FALSE';
        $info["courierActive"] = ($courieractive) ? 'TRUE' : 'FALSE';
        $info["webmailActive"] = ($webmailactive) ? 'TRUE' : 'FALSE';
        $info["authsmtpActive"] = ($authsmtpactive) ? 'TRUE' : 'FALSE';
        #$info["amavisBypassSpamChecks"] = ($amavisBypassSpamChecks) ? 'TRUE' : 'FALSE';
        if (!ldap_mod_replace($this->conn,  self::getBaseDN(), $info)) {
            $error = ldap_error($this->conn);
            throw new Exception("Erreur pendant la modification du compte : $error");
        }
    }

    public function isActive() {
        return $this->active;
    }

    public function isAdmin() {
        return $this->admin;
    }

    public function getUid() {
        return $this->uid;
    }

    public function getName() {
        return $this->name;
    }

    public function getAliases() {
        return preg_replace('/@'.$this->domain->getName().'/', '', $this->aliases);
    }

    public function getRedirections() {
        return $this->redirections;
    }

    public function isCourier() {
        return $this->courier;
    }

    public function isAuthSmtp() {
        return $this->authsmtp;
    }

    public function __destruct() {
        return true;
    }
}

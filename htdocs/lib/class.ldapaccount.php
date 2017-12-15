<?php

class LdapAccount extends LdapDomain {
    protected $domain,$uid,$name,$active=false,$admin=false,$courier=false,$authsmtp=false;

    public function __construct($domain, $uid) {
        $this->conn = $domain->conn;
        $this->domain = $domain->getName();

        $this->uid = $uid;
        if ($sr = @ldap_search($this->conn, "uid=".$uid.",cn=".$this->domain.",".LDAP_BASE, "(ObjectClass=mailAccount)")) {
            $objects = ldap_get_entries($this->conn, $sr);
            $object = $objects[0];
            $this->name = $object['cn'][0];
            $this->active = ($object['isactive'][0] == 'TRUE') ? true : false;
            $this->admin = ($object['isadmin'][0] == 'TRUE') ? true : false;
            $this->courier = ($object['courieractive'][0] == 'TRUE') ? true : false;
            $this->authsmtp = ($object['authsmtpactive'][0] == 'TRUE') ? true : false;
            //$this->quota = getquota($this->domain,'user');
        } else {
            throw new Exception("Ce compte n'existe pas !");
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
        return array();
    }

    public function getRedirections() {
        return array();
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

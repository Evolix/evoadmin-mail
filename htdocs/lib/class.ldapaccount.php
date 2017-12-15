<?php

class LdapAccount extends LdapDomain {
    protected $domain,$uid,$name,$active=false;

    public function __construct($domain, $uid) {
        $this->conn = $domain->conn;
        $this->domain = $domain->getName();

        $this->uid = $uid;
        if ($sr = @ldap_search($this->conn, "uid=".$uid.",cn=".$this->domain.",".LDAP_BASE, "(ObjectClass=mailAccount)")) {
            $objects = ldap_get_entries($this->conn, $sr);
            $object = $objects[0];
            $this->name = $object['cn'][0];
            //$this->quota = getquota($this->domain,'user');
        } else {
            throw new Exception("Ce compte n'existe pas !");
        }
    }

    public function getUid() {
        return $this->uid;
    }

    public function getName() {
        return $this->name;
    }

    public function __destruct() {
        return true;
    }
}

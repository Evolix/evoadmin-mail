<?php

class LdapAlias extends LdapDomain {
    protected $domain,$name,$active=false;
    private $aliases=array(),$redirections=array();

    public function __construct($domain, $name) {
        $this->conn = $domain->conn;
        $this->domain = $domain->getName();

        $this->name = $name;
        if ($sr = @ldap_search($this->conn, "cn=".$name.",cn=".$this->domain.",".LDAP_BASE, "(ObjectClass=mailAlias)")) {
            $objects = ldap_get_entries($this->conn, $sr);
            $object = $objects[0];
            $this->active = ($object['isactive'][0] == 'TRUE') ? true : false;
            $this->aliases = array_filter($object['mailacceptinggeneralid'], "is_string");
            $this->redirections = array_filter($object['maildrop'], "is_string");
        } else {
            throw new Exception("Cet alias n'existe pas !");
        }
    }

    public function isActive() {
        return $this->active;
    }
   
    public function getName() {
        return $this->name;   
    }
 
    public function getAliases() {
        return preg_replace('/@'.$this->domain.'/', '', $this->aliases);
    }

    public function getRedirections() {
        return $this->redirections;
    }

    public function __destruct() {
        return true;
    }
}

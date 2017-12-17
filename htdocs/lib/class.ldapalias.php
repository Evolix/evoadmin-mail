<?php

class LdapAlias extends LdapDomain {
    static $objectClass = array('mailAlias');

    static public function getClassFilter() {
        return '(ObjectClass='.self::$objectClass[0].')';
    }

    protected $domain,$name,$active=false;
    private $aliases=array(),$redirections=array();

    public function __construct(LdapDomain $domain, $name) {
        $this->conn = $domain->conn;
        $this->domain = $domain->getName();

        $this->name = $name;
        if ($sr = @ldap_search($this->conn, "cn=".$name.",cn=".$this->domain.",".LDAP_BASE, self::getClassFilter())) {
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

    public function update($active=false,$mailaccept=array(),$maildrop=array()) {
        $info["isActive"] = ($active) ? 'TRUE' : 'FALSE';
        $info["mailacceptinggeneralid"] = $mailaccept;
        $info["maildrop"] = array_filter($maildrop, function($value) {
            return filter_var($value, FILTER_VALIDATE_EMAIL);
        });

        if (!@ldap_mod_replace($this->conn, "cn=".$this->getName().",cn=".$this->domain.",".LDAP_BASE, $info)) {
            $error = ldap_error($this->conn);
            throw new Exception("Erreur pendant la modification de l'alias : $error");
        }
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

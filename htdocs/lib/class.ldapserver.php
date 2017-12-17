<?php

class LdapServer {
    private $conn=NULL,$login,$base,$superadmin=false,$domains=array();

    static public function getClassFilter() {
        return '(ObjectClass='.static::$objectClass[0].')';
    }

    static public function getBaseDN($object, $name=NULL) {
        $class = get_called_class();
        if ($class == "LdapDomain") {
            if (empty($name)) {
                if ($object->server->isSuperadmin()) {
                    return static::$dn.'='.$object->getName().','.LdapServer::getBaseDN($object->server);
                } else {
                    $mydomain = preg_replace('/.*@/', '', $object->server->login);
                    if ($object->getName() == $mydomain) {
                        return $object->server->base;
                    } else {
                        throw new Exception("Vous n'etes pas autoriser a acceder a cette page");
                    }
                }
            } else {
                if ($object->isSuperadmin()) {
                    return static::$dn.'='.$name.','.LdapServer::getBaseDN($object);
                } else {
                    throw new Exception("Vous n'etes pas autoriser a acceder a cette page");
                }
            }
        } elseif ($class == "LdapAccount") {
            if (empty($name)) {
                return static::$dn.'='.$object->getUid().','.LdapDomain::getBaseDN($object->domain);
            } else {
                return static::$dn.'='.$name.','.LdapDomain::getBaseDN($object);
            }
        } elseif ($class == "LdapAlias") {
            if (empty($name)) {
                return static::$dn.'='.$object->getName().','.LdapDomain::getBaseDN($object->domain);
            } else {
                return static::$dn.'='.$name.','.LdapDomain::getBaseDN($object);
            }
        } else {
            return $object->base;
        }
    }

    public function __construct($login, $base, $adminDN, $adminPass, $uri='ldap://127.0.0.1') {
        global $conf;
        $this->login = $login;
        if (!$this->conn = ldap_connect($uri)) {
            throw new Exception("Impossible de se connecter au serveur LDAP $uri");
        }
        if (!ldap_set_option($this->conn, LDAP_OPT_PROTOCOL_VERSION, 3)) {
            throw new Exception("Impossible de modifier la version du protocole LDAP à 3");
        }
        if (!ldap_bind($this->conn, $adminDN, $adminPass)) {
            throw new Exception("Authentification LDAP échoué !");
        }
        if (in_array($this->login, $conf['admin']['logins'])) {
            $this->superadmin = true;
            $this->base = $base;
        } else {
            $mydomain = preg_replace('/.*@/', '', $login);
            $this->base = LdapDomain::$dn.'='.$mydomain.','.$base;
        }
    }

    public function login($password) {
        $sr=ldap_search($this->conn, self::getBaseDN($this), "(&(uid=".$this->login.")(isAdmin=TRUE))");
        $info = ldap_get_entries($this->conn, $sr);
        if (!$info['count'] || !@ldap_bind($this->conn, $info[0]['dn'], $password)) {
            throw new Exception("&Eacute;chec de l'authentification, utilisateur ou mot de passe incorrect.");
        }
    }

    public function getDomains() {
        if (count($this->domains) == 0) {
            $sr = ldap_search($this->conn, self::getBaseDN($this), LdapDomain::getClassFilter());
            $objects = ldap_get_entries($this->conn, $sr);
            foreach($objects as $object) {
                if(!empty($object[LdapDomain::$dn][0])) {
                    $domain = new LdapDomain($this, $object[LdapDomain::$dn][0]);
                    array_push($this->domains, $domain);
                }
            }
            sort($this->domains);
        }
        return $this->domains;
    }

    public function addDomain($name,$active=false) {
        $info[LdapDomain::$dn]=$name;
        $info["objectclass"] = LdapDomain::$objectClass;
        $info["isActive"] = ($active) ? 'TRUE' : 'FALSE';
        $info["gidNumber"]= getfreegid();

        if (!@ldap_add($this->conn, LdapDomain::getBaseDN($this, $name), $info)) {
            $error = ldap_error($this->conn);
            throw new Exception("Erreur dans l'ajout du domaine : $error");
        }
    }

    public function delDomain($name) {
        if ($domain = new LdapDomain($this, $name)) {
            // Delete aliases
            foreach($domain->getAlias() as $alias) {
                $domain->delAlias($alias->getName());
            }
            // Delete accounts
            foreach($domain->getAccounts() as $account) {
                $domain->delAccount($account->getUid());
            }
            // Delete domain
            $dn = LdapDomain::getBaseDN($this, $name);
            if (!ldap_delete($this->conn, $dn)) {
                $error = ldap_error($this->conn);
                throw new Exception("Erreur dans la suppression du domaine $dn : $error");
            }
        } else {
            throw new Exception("Ce domaine n'existe pas !");
        }
    }

    public function isSuperAdmin() {
        return $this->superadmin;
    }
    
    public function getLogin() {
        return $this->login;
    }

    public function getDn() {
        return $this->dn;
    }

    public function getConn() {
        return $this->conn;
    }

    public function __destruct() {
        ldap_unbind($this->conn);
    }
}

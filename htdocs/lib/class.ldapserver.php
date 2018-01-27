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

    static protected function hashPassword($pass) {
        if (strlen($pass) > 42 || strlen($pass) < 5 || !preg_match('/^([[:graph:]]*)$/',$pass)) {
            throw new Exception("Mot de passe invalide, voir page d'aide");
        }
        mt_srand((double)microtime()*1000000);
        $salt = mhash_keygen_s2k(MHASH_SHA1, $pass, substr(pack('h*', md5(mt_rand())), 0, 8), 4);
        return '{SSHA}'.base64_encode(mhash(MHASH_SHA1, $pass.$salt).$salt);
    }

    public function __construct($login, $config) {
        $uri = 'ldap://'.$config['host'].':'.$config['port'];
        $this->login = $login;
        if (!$this->conn = ldap_connect($uri)) {
            throw new Exception("Impossible de se connecter au serveur LDAP ".$config['host']);
        }
        if (!ldap_set_option($this->conn, LDAP_OPT_PROTOCOL_VERSION, 3)) {
            throw new Exception("Impossible de modifier la version du protocole LDAP à 3");
        }
        if (!ldap_bind($this->conn, $config['admin_dn'], $config['admin_pass'])) {
            throw new Exception("Authentification LDAP échoué !");
        }
        if (in_array($this->login, $config['superadmin'])) {
            $this->superadmin = true;
            $this->base = $config['base'];
        } else {
            $mydomain = preg_replace('/.*@/', '', $login);
            $this->base = LdapDomain::$dn.'='.$mydomain.','.$config['base'];
        }
    }

    public function login($password) {
        $sr=ldap_search($this->conn, self::getBaseDN($this), "(&(uid=".$this->login.")(isAdmin=TRUE))");
        $info = ldap_get_entries($this->conn, $sr);
        if (!$info['count']) {
            Logger::error('invalid login '.$this->login);
            throw new Exception("&Eacute;chec de l'authentification, utilisateur ou mot de passe incorrect.");
        }

        if (!@ldap_bind($this->conn, $info[0]['dn'], $password)) {
            Logger::error('invalid password for user '.$this->login);
            throw new Exception("&Eacute;chec de l'authentification, utilisateur ou mot de passe incorrect.");
        }
        Logger::info($this->login.' successfully logged in');
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
        $info["gidNumber"] = $this->getFree('gidnumber');

        if (!@ldap_add($this->conn, LdapDomain::getBaseDN($this, $name), $info)) {
            $error = ldap_error($this->conn);
            Logger::error('error when adding domain '.$name, $this->login);
            throw new Exception("Erreur dans l'ajout du domaine : $error");
        }
        Logger::info('domain '.$name.' added', $this->login);
        //domainnotify($name);
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
                Logger::error('error when delete domain '.$name, $this->login);
                throw new Exception("Erreur dans la suppression du domaine $dn : $error");
            }
            Logger::info('domain '.$name.' deleted ', $this->login);
        } else {
            Logger::error('trying to delete an unknow domain '.$name, $this->login);
            throw new Exception("Ce domaine n'existe pas !");
        }
    }

    protected function getFree($type) {
        $sr = ldap_search($this->conn, self::getBaseDN($this), '('.$type.'=*)');
        $info = ldap_get_entries($this->conn, $sr);
        $id = 1;
        foreach ($info as $entry) {
            $id = ($entry[$type][0] >= $id) ? (int) $entry[$type][0] : $id;
        }
        $id++; 
        return $id;
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

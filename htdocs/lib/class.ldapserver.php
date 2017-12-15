<?php

class LdapServer {
    protected $conn=NULL,$login,$dn,$superadmin=false;
    private $domains=array();

    public function __construct($login) {
        global $conf;
        $this->login = $login;
        if (!$this->conn = ldap_connect(LDAP_URI)) {
            throw new Exception("Impossible de se connecter au serveur LDPA ".LDAP_URI);
        }
        if (!ldap_set_option($this->conn, LDAP_OPT_PROTOCOL_VERSION, 3)) {
            throw new Exception("Impossible de modifier la version du protocole LDAP à 3");
        }
        if (!ldap_bind($this->conn, LDAP_ADMIN_DN, LDAP_ADMIN_PASS)) {
            throw new Exception("Authentification LDAP échoué !");
        }
        if (in_array($this->login, $conf['admin']['logins'])) {
            $this->superadmin = true;
        }
        return $this;
    }

    public function login($password) {
        global $conf;
        $sr=ldap_search($this->conn, LDAP_BASE, "(&(uid=".$this->login.")(isAdmin=TRUE))");
        $info = ldap_get_entries($this->conn, $sr);
        if ($info['count']) {
            if (@ldap_bind($this->conn, $info[0]['dn'], $password)) {
                unset($password);
                $this->dn = $info[0]['dn'];
#                EvoLog::log("Login success for " . $this->login);
                return true;
            } else {
                $this->__destruct();
#                EvoLog::log("Password failed : " . $this->login);
                return false;
            }
        } else {
            $this->__destruct();
#            EvoLog::log("Login failed : " . $this->login);
            return false;
        }
    }

    public function getDomains() {
        global $conf;
        if (count($this->domains) == 0) {
            if ($this->superadmin) {
                $filter = ($conf['evoadmin']['version'] == 1) ? '(objectClass=ldapDomain)' : '(objectClass=postfixDomain)';
                $sr = ldap_search($this->conn, LDAP_BASE, $filter);
                $objects = ldap_get_entries($this->conn, $sr);
                foreach($objects as $object) {
                    if(!empty($object["cn"][0])) {
                        $domain = new LdapDomain($this, $object["cn"][0]);
                        array_push($this->domains, $domain);
                    }
                }
                sort($this->domains);
            } else {
                $filter = ($conf['evoadmin']['version'] <= 2) ? ',domain=((?:(?:[0-9a-zA-Z_\-]+)\.){1,}(?:[0-9a-zA-Z_\-]+)),' : ',cn=((?:(?:[0-9a-zA-Z_\-]+)\.){1,}(?:[0-9a-zA-Z_\-]+)),';
                $mydomain = preg_replace("/uid=".$login.$filter.LDAP_BASE."/",'$1',$this->dn);
                array_push($this->domains,$mydomain);
            }
        }
        return $this->domains;
    }

    public function addDomain($name,$active='FALSE') {
        global $conf;
        $info["cn"]=$name;
        $info["objectclass"][0] = ($conf['evoadmin']['version'] == 1) ? 'ldapDomain' : 'postfixDomain';
        $info["objectclass"][1] = "posixGroup";
        $info["postfixTransport"] = "virtual:";
        $info["isActive"] = $active;
        $info["gidNumber"]= getfreegid();

        if (!@ldap_add($this->conn, "cn=".$name.",".LDAP_BASE, $info)) {
            $error = ldap_error($this->conn);
            throw new Exception("Erreur dans l'ajout du domaine : $error");
        }
    }

    public function delDomain($name) {
        if ($sr = @ldap_search($this->conn, "cn=".$name.",".LDAP_BASE, "(ObjectClass=*)")) {
            $objects = ldap_get_entries($this->conn, $sr);
            // Delete aliases
            foreach($objects as $object) {
                if (!empty($object['objectclass']) && !in_array("postfixDomain", $object['objectclass']) && in_array("mailAlias", $object['objectclass'])) {
                    $dn = "cn=".$object['cn'][0]. ",cn=".$name.",".LDAP_BASE;
                    if (!ldap_delete($this->conn, $dn)) {
                        $error = ldap_error($this->conn);
                        throw new Exception("Erreur dans la suppression de l'alias $dn : $error");
                    }
                }
            }
            // Delete accounts
            foreach($objects as $object) {
                if (!empty($object['objectclass']) && !in_array("postfixDomain", $object['objectclass']) && !in_array("mailAlias", $object['objectclass'])) {
                    $dn = "uid=".$object['cn'][0]. ",cn=".$name.",".LDAP_BASE;
                    if (!ldap_delete($this->conn, $dn)) {
                        $error = ldap_error($this->conn);
                        throw new Exception("Erreur dans la suppression du compte $dn : $error");
                    }
                }
            }
            // Delete domain
            $dn = "cn=".$name.",".LDAP_BASE;
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

    public function __destruct() {
        ldap_unbind($this->conn);
    }
}

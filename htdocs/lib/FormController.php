<?php

class FormController extends DefaultController {
    private static $form=array(), $domain, $account, $alias;
    public static function init() {
        if (self::$logged) { 
            self::filterPost();
            // Get content from LDAP
            try {
                if (!empty(self::$form['domain'])) {
                    self::$domain = new LdapDomain(self::$server, self::$form['domain']);
                    if (!empty(self::$form['account'])) {
                        self::$account = new LdapAccount(self::$domain, self::$form['account']);
                    }
                    if (!empty(self::$form['alias'])) {
                        self::$alias = new LdapAlias(self::$domain, self::$form['alias']);
                    }
                }
            } catch (Exception $e) {
                self::$alerts[] = array('type' => 2, 'message' => $e->getMessage());
            }

            if (!empty(self::$form['delete'])) {
                switch(self::$form['delete']) {
                    case 'domain':
                        FormController::delDomain();
                        break;
                    case 'account':
                        FormController::delAccount();
                        break;
                    case 'alias':
                        FormController::delAlias();
                        break;
                }
            } else if (!empty(self::$form['add'])) {
                switch(self::$form['add']) {
                    case 'domain':
                        FormController::addDomain();
                        break;
                    case 'account':
                        FormController::addAccount();
                        break;
                    case 'alias':
                        FormController::addAlias();
                        break;
                }
            } else if (!empty(self::$form['update'])) {
                switch(self::$form['update']) {
                    case 'domain':
                        FormController::updateDomain();
                        break;
                    case 'account':
                        FormController::updateAccount();
                        break;
                    case 'alias':
                        FormController::updateAlias();
                        break;
                }
            }
        } 
    }

    private static function filterPassword() {
        if (count(self::$form['password']) != 2 || self::$form['password'][0] != self::$form['password'][1]) {
            self::$alerts[] = array('type' => 2, 'message' => "Confirmation du mot de passe inccorrecte !");
            return false;

        }
        self::$form['password'] = self::$form['password'][0];
        self::$form['password'] = filter_var(self::$form['password'], FILTER_CALLBACK, array('options' => function($value) {
            return trim($value);
        }));
    }

    private static function filterType($type) {
        if (in_array($type, array('domain', 'account', 'alias'))) {
            return $type;
        } else { return NULL; }
    }

    private static function filterPost() {
        self::$form = filter_input_array(INPUT_POST, array(
            'add' => array('filter' => FILTER_CALLBACK, 'options' => 'self::filterType')
            ,'delete' => array('filter' => FILTER_CALLBACK, 'options' => 'self::filterType')
            ,'update' => array('filter' => FILTER_CALLBACK, 'options' => 'self::filterType')
            ,'domain' => FILTER_SANITIZE_URL
            ,'account' => FILTER_SANITIZE_EMAIL
            ,'alias' => array('filter' => FILTER_SANITIZE_STRING, 'flags' => FILTER_FLAG_STRIP_HIGH)
            ,'uid' => array('filter' => FILTER_SANITIZE_STRING, 'flags' => FILTER_FLAG_STRIP_HIGH)
            ,'cn' => array('filter' => FILTER_SANITIZE_STRING, 'flags' => FILTER_FLAG_STRIP_HIGH)
            ,'password' => array('filter' => FILTER_DEFAULT, 'flags' => FILTER_FORCE_ARRAY)
            ,'isactive' => FILTER_VALIDATE_BOOLEAN
            ,'isadmin' => FILTER_VALIDATE_BOOLEAN
            ,'courieractive' => FILTER_VALIDATE_BOOLEAN
            ,'webmailactive' => FILTER_VALIDATE_BOOLEAN
            ,'authsmtpactive' => FILTER_VALIDATE_BOOLEAN
            ,'maildrop' => array('filter' => FILTER_DEFAULT, 'flags' => FILTER_FORCE_ARRAY)
            ,'mailaccept' => array('filter' => FILTER_DEFAULT, 'flags' => FILTER_FORCE_ARRAY)
        ), true);

        if (!empty(self::$form['password'])) { self::filterPassword(); }

        unset($_POST);
        //die(var_dump(self::$form));
    }

    private static function addDomain() {
        if (self::needSuperAdmin()) {
            if (!empty(self::$form['cn'])) {
                try {
                    self::$alerts[] = array('type' => 1, 'message' => 'Ajout en cours du domaine '.self::$form['cn'].' ...');
                    self::$server->addDomain(self::$form['cn'], self::$form['isactive']);
                    self::$alerts[] = array('type' => 0, 'message' => "Ajout effectué.");
                } catch (Exception $e_ad) {
                    self::$alerts[] = array('type' => 2, 'message' => $e_ad->getMessage());
                }
            }
        }
    }

    private static function updateDomain() {
        if (self::needSuperAdmin()) {
            try {
                self::$domain->update(self::$form['isactive']);
            } catch (Exception $e_ad) {
                self::$alerts[] = array('type' => 2, 'message' => $e_ad->getMessage());
            }
        }
    }

    private static function delDomain() {
       if (self::needSuperAdmin()) {
           self::$alerts[] = array('type' => 1, 'message' => 'Suppression du domaine '.self::$form['cn'].' ...');
           try {
               self::$server->delDomain(self::$form['cn']);
               self::$alerts[] = array('type' => 0, 'message' => 'Suppression effectué.');
           } catch (Exception $e_ad) {
               self::$alerts[] = array('type' => 2, 'message' => $e_ad->getMessage());
           }
       }
    }

    private static function delAccount() {
        self::$alerts[] = array('type' => 1, 'message' => 'Suppression du compte '.self::$form['uid'].'...');
        try {
            self::$domain->delAccount(self::$form['uid']);
            self::$alerts[] = array('type' => 0, 'message' => "Suppression effectué.");
        } catch (Exception $e) {
            self::$alerts[] = array('type' => 2, 'message' => $e->getMessage());
        }
    }

    private static function delAlias() {
        self::$alerts[] = array('type' => 1, 'message' => 'Suppression de l\'alias '.self::$form['cn'].'...');
        try {
            self::$domain->delAlias(self::$form['cn']);
            self::$alerts[] = array('type' => 0, 'message' => "Suppression effectué.");
        } catch (Exception $e) {
            self::$alerts[] = array('type' => 2, 'message' => $e->getMessage());
        }
    }

    private static function addAccount() {
        try {
            self::$alerts[] = array('type' => 1, 'message' => "Ajout en cours...");
            self::$domain->addAccount(
                self::$form['uid']
                ,self::$form['cn']
                ,self::$form['password']
                ,self::$form['isactive']
                ,self::$form['isadmin']
                ,self::$form['isactive']
                ,self::$form['courieractive']
                ,self::$form['webmailactive']
                ,self::$form['authsmtpactive']
            );
            self::$alerts[] = array('type' => 0, 'message' => 'Ajout effectué');
        } catch (Exception $e) {
            self::$alerts[] = array('type' => 2, 'message' => $e->getMessage());
        }
    }

    private static function updateAccount() {
        try {
            self::$alerts[] = array('type' => 1, 'message' => "Modification en cours...");
            self::$account->update(
                self::$form['cn']
                ,self::$form['password']
                ,self::$form['isactive']
                ,self::$form['isadmin']
                ,self::$form['isactive']
                ,self::$form['courieractive']
                ,self::$form['webmailactive']
                ,self::$form['authsmtpactive']
            );
            self::$alerts[] = array('type' => 0, 'message' => "Modification effectué.");
        } catch (Exception $e) {
            self::$alerts[] = array('type' => 2, 'message' => $e->getMessage());
        }
    }

    private static function addAlias() {
        try {
             self::$alerts[] = array('type' => 1, 'message' => "Ajout en cours...");
             self::$domain->addAlias(
                 self::$form['cn']
                 ,self::$form['isactive']
                 ,self::$form['mailaccept']
                 ,self::$form['maildrop']
             );
             self::$alerts[] = array('type' => 0, 'message' => "Ajout effectué");
        } catch (Exception $e) {
           self::$alerts[] = array('type' => 2, 'message' => $e->getMessage());
        }
    }

    private static function updateAlias() {
        try {
            self::$alerts[] = array('type' => 1, 'message' => "Modification en cours...");
            self::$alias->update(
                self::$form['isactive']
                ,self::$form['mailaccept']
                ,self::$form['maildrop']
            );
            self::$alerts[] = array('type' => 0, 'message' => "Modification effectué.");
        } catch (Exception $e) {
           self::$alerts[] = array('type' => 2, 'message' => $e->getMessage());
        }

    }
}

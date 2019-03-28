<?php

if (file_exists(stream_resolve_include_path('Twig/autoload.php'))) {
    require_once 'Twig/autoload.php';
} elseif (stream_resolve_include_path(file_exists('Twig/Autoloader.php'))) {
    require_once 'Twig/Autoloader.php';
    Twig_Autoloader::register();
}

class PageController {
    public static $alerts=array();
    private static $server, $twig, $params=array(), $domain, $account, $alias;

    public static function init(LdapServer $server=NULL) {
        self::$server = $server;

        $loader = new Twig_Loader_Filesystem('tpl/page');
        self::$twig = new Twig_Environment($loader, array(
            'cache' => false
        ));

        ob_start();

        if (!empty(self::$server)) {
            PageController::filterGet();
            PageController::ldap();
            if (!empty(self::$params['page'])) {
                switch(self::$params['page']) {
                    case 'logout':
                        PageController::logout();
                        break;
                    case 'help':
                        PageController::help();
                        break;
                }
            } else {
                PageController::choosePage();
            }
        } else {
            PageController::login();
        }

        ob_end_flush();
    }

    private static function needSuperAdmin() {
        if (!self::$server->isSuperAdmin()) {
            self::$alerts[] = array('type' => 2, 'message' => "Super Administrateur seulement !");
            print self::$twig->render('403.html', array(
                'page_name' => Config::getName()
                ,'alerts' => self::$alerts
                ,'login' => self::$server->getLogin()
                ,'isSuperAdmin' => self::$server->isSuperAdmin()
                ));
            header('HTTP/1.1 403 Forbidden');
            exit(0);
        }
    }

    private static function filterGet() {
        $allowed_params = array('_all', '_add');
        $static_pages = array('logout', 'help');

        self::$params['page'] = !empty($_GET['page']) && in_array($_GET['page'], $static_pages) ? $_GET['page'] : NULL;
        if (!empty($_GET['domain']) && in_array($_GET['domain'], $allowed_params)) { self::$params['domain'] = $_GET['domain']; }
        if (!empty($_GET['account']) && in_array($_GET['account'], $allowed_params)) { self::$params['account'] = $_GET['account']; }
        if (!empty($_GET['alias']) && in_array($_GET['alias'], $allowed_params)) { self::$params['alias'] = $_GET['alias']; }
        self::$params = array_merge(filter_input_array(INPUT_GET, array(
                'domain' => FILTER_SANITIZE_URL
                ,'account' => FILTER_SANITIZE_EMAIL
                ,'alias' => array('filter' => FILTER_SANITIZE_STRING, 'flags' => FILTER_FLAG_STRIP_HIGH)
            ), true)
        , self::$params);

        unset($_GET);
        //die(var_dump(self::$params));
    }

    private static function ldap() {
        // Get content from LDAP
        try {
            if (!empty(self::$params['domain']) && self::$params['domain'] != '_all' && self::$params['domain'] != '_add') {
                self::$domain = new LdapDomain(self::$server, self::$params['domain']);
                if (!empty(self::$params['account']) && self::$params['account'] != '_all' && self::$params['account'] != '_add') {
                    self::$account = new LdapAccount(self::$domain, self::$params['account']);
                }
                if (!empty(self::$params['alias']) && self::$params['alias'] != '_all' && self::$params['alias'] != '_add') {
                    self::$alias = new LdapAlias(self::$domain, self::$params['alias']);
                }
            }
        } catch (Exception $e) {
            self::$alerts[] = array('type' => 2, 'message' => $e->getMessage());
        }
    }

    private static function login() {
        print self::$twig->render('login.html', array(
            'page_name' => Config::getName().' - Login'
            ,'alerts' => self::$alerts
            ,'logout' => false
        ));
    }

    private static function logout() {
        session_unset('EVOADMIN_SESS');
        session_destroy();
        print self::$twig->render('login.html', array(
            'page_name' => Config::getName().' - Login'
            ,'alerts' => self::$alerts
            ,'logout' => true
        ));
    }

    private static function help() {
        print self::$twig->render('help.html', array(
            'page_name' => Config::getName() 
            ,'alerts' => self::$alerts
            ,'login' => self::$server->getLogin()
            ,'isSuperAdmin' => self::$server->isSuperAdmin()
            ,'notif_mail' => Config::getMail()
        ));
    }

    private static function choosePage() {
        if (empty(self::$params['domain'])) {
            self::$params['domain'] = '_all';
        }
        if (self::$params['domain'] == '_all') {
            PageController::listDomains();
        } else if (self::$params['domain'] == '_add') {
            PageController::addDomain();
        } else {
            if (empty(self::$params['account']) && empty(self::$params['alias'])) { self::$params['account'] = '_all'; }
            if (!empty(self::$params['account'])) {
                if (self::$params['account'] == '_all') {
                    PageController::listAccounts();
                } else {
                    PageController::Account();
                }
            } else if (!empty(self::$params['alias']) && empty(self::$params['account'])) {
                if (self::$params['alias'] == '_all') {
                    PageController::listAlias();
                } else {
                    PageController::Alias();
                }
            }
        }
    }

    private static function addDomain() {
        self::needSuperAdmin();
        print self::$twig->render('add_domain.html', array(
            'page_name' => Config::getName()
            ,'alerts' => self::$alerts
            ,'login' => self::$server->getLogin()
            ,'isSuperAdmin' => self::$server->isSuperAdmin()
        ));
    }

    private static function listDomains() {
        print self::$twig->render('list_domain.html', array(
            'page_name' => Config::getName()
            ,'alerts' => self::$alerts
            ,'login' => self::$server->getLogin()
            ,'isSuperAdmin' => self::$server->isSuperAdmin()
            ,'domains' => self::$server->getDomains()
        ));
    }

    private static function listAccounts() {
        print self::$twig->render('list_account.html', array(
            'page_name' => Config::getName()
            ,'alerts' => self::$alerts
            ,'login' => self::$server->getLogin()
            ,'isSuperAdmin' => self::$server->isSuperAdmin()
            ,'domain' => self::$domain->getName()
            ,'active' => self::$domain->isActive()
            ,'accounts' => self::$domain->getAccounts()
            ,'view' => 'account'
        ));
    }

    private static function listAlias() {
        print self::$twig->render('list_alias.html', array(
            'page_name' => Config::getName()
            ,'alerts' => self::$alerts
            ,'login' => self::$server->getLogin()
            ,'isSuperAdmin' => self::$server->isSuperAdmin()
            ,'domain' => self::$domain->getName()
            ,'active' => self::$domain->isActive()
            ,'aliases' => self::$domain->getAlias()
            ,'view' => 'alias'
        ));
    }

    private static function Account() {
        print self::$twig->render('account.html', array(
            'page_name' => Config::getName()
            ,'alerts' => self::$alerts
            ,'login' => self::$server->getLogin()
            ,'isSuperAdmin' => self::$server->isSuperAdmin()
            ,'domain' => self::$domain->getName()
            ,'uid' => !empty(self::$account) ? self::$account->getUid() : NULL
            ,'name' => !empty(self::$account) ? self::$account->getName() : NULL
            ,'aliases' => !empty(self::$account) ? self::$account->getAliases() : array()
            ,'maildrops' => !empty(self::$account) ? self::$account->getRedirections() : array()
            ,'active' => !empty(self::$account) ? self::$account->isActive() : true
            ,'admin' => !empty(self::$account) ? self::$account->isAdmin() : false
            ,'courier' => !empty(self::$account) ? self::$account->isCourier() : true
            ,'webmail' => !empty(self::$account) ? self::$account->isWebmail() : true
            ,'authsmtp' => !empty(self::$account) ? self::$account->isAuthSmtp() : true
        ));
    }

    private static function Alias() {
        print self::$twig->render('alias.html', array(
            'page_name' => Config::getName()
            ,'alerts' => self::$alerts
            ,'login' => self::$server->getLogin()
            ,'isSuperAdmin' => self::$server->isSuperAdmin()
            ,'domain' => self::$domain->getName()
            ,'name' => !empty(self::$alias) ? self::$alias->getName() : NULL
            ,'active' => !empty(self::$alias) ? self::$alias->isActive() : true
            ,'aliases' => !empty(self::$alias) ? self::$alias->getAliases() : NULL
            ,'maildrops' => !empty(self::$alias) ? self::$alias->getRedirections() : NULL
        ));
    }
}

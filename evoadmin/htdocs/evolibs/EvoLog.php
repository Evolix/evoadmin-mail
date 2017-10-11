<?php


/*
 * gestion des Logs
 * inspire de Horde
 */


class EvoLog
{

    function log($message, $priority = PEAR_LOG_INFO)
    {

        global $conf;

        if (!$conf['log']['enabled'])
        {
            return;
        }

        $logger = Log::singleton('file', $conf['log']['name'] ,'evoadmin');
        $logger->log($message, $priority);
    }

    function debug() 
    {
        if (DEBUG > 2)
        {
            echo "<hr /><hr /> SESSION DEBUG : <br />";
            print_r($_SESSION);
            echo "<br />"; 
            echo 'session_name() : ' . session_name() . "<br>\n" ;
            echo 'session_id() : ' . session_id() . "<br>\n" ;
            echo 'session_cache_expire() : ' . session_cache_expire() . "<br>\n" ;
            echo 'session_cache_limiter() : ' . session_cache_limiter() . "<br>\n" ;
            echo 'session_get_cookie_params() : ';
            print_r(array_values(session_get_cookie_params()));
            echo "<br>\n";
            echo 'session_module_name() : ' . session_module_name() . "<br>\n" ;
            echo 'session_save_path() : ' . session_save_path() . "<br>\n" ;

            echo "<hr /><hr /> POST DEBUG : <br />";
            print_r($_POST);
            echo "<hr /><hr /> GET DEBUG : <br />";
            print_r($_GET);
            echo "<hr /><hr />";

        }

    }

}

?>

<?php

session_name('EVOADMIN_SESS');
session_start();

if (isset($_SESSION['login'])) {
    header("Location: superadmin.php\n\n");
    exit(0);
} else {
    header("Location: auth.php\n\n");
    exit(0);
}
?>

<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/debug.php');
if (! isset($_SESSION['admin_user'])) {
    session_destroy();
    redirect($prefix.'go/su/su');
    die();
}

$admin_user = $_SESSION['admin_user'];
$role = find_role($admin_user, '', true);
if ($role & ROLE_SYSADMIN) {
    setup_session($role, $admin_user);
    unset($_SESSION['admin_user']);
    header('Location: '.$prefix.'go/su/su');
    die();
} elseif ($role & ROLE_CUSTOMER) {
    setup_session($role, $admin_user);
    unset($_SESSION['admin_user']);
    header('Location: '.$prefix.'go/su/su_customer');
    die();
}

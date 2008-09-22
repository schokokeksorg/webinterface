<?php

require_once('inc/debug.php');

if (isset($_SESSION['admin_user']) ) {
  $admin_user = $_SESSION['admin_user'];
  $role = find_role($admin_user, '', True);
  if ($role & ROLE_SYSADMIN) {
    setup_session($role, $admin_user);
    unset($_SESSION['admin_user']);
    header('Location: '.$prefix.'go/su/su');
    die();
  }
}
system_failure('Unprivilleged action');



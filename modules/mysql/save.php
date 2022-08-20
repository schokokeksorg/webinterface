<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('session/start.php');
require_once('inc/icons.php');
require_role([ROLE_SYSTEMUSER]);

global $prefix;

require_once('mysql.php');

if (isset($_GET['action']) && $_GET['action'] == 'permchange') {
    check_form_token('mysql_permchange');
    set_mysql_access($_GET['db'], $_GET['user'], ($_GET['access'] == 1));
    redirect('overview');
}

if (isset($_GET['action']) && $_GET['action'] == 'newdb') {
    check_form_token('mysql_newdb');
    $dbname = $_POST['newdb'];
    $desc = $_POST['description'];
    $server = null;
    if (isset($_POST['server'])) {
        $server = $_POST['server'];
    }
    create_mysql_database($dbname, $desc, $server);
    if (isset($_POST['access'])) {
        foreach ($_POST['access'] as $user) {
            set_mysql_access($dbname, $user, true);
        }
    }
    redirect('overview');
}

if (isset($_GET['action']) && $_GET['action'] == 'newuser') {
    check_form_token('mysql_newuser');
    $username = $_POST['newuser'];
    $desc = $_POST['description'];
    $password = $_POST['newpass'];
    $check = strong_password($password);
    if ($check !== true) {
        system_failure('Das Passwort ist nicht sicher genug.');
    }
    create_mysql_account($username, $desc);
    set_mysql_password($username, $password);
    if (isset($_POST['access'])) {
        foreach ($_POST['access'] as $dbname) {
            set_mysql_access($dbname, $username, true);
        }
    }
    redirect('overview');
}

if (isset($_GET['action']) && $_GET['action'] == 'description') {
    check_form_token('mysql_description');
    if (isset($_GET['db'])) {
        $db = $_GET['db'];
        $description = $_POST['description'];
        set_database_description($db, $description);
    }
    if (isset($_GET['username'])) {
        $user = $_GET['username'];
        $description = $_POST['description'];
        set_dbuser_description($user, $description);
    }
    redirect('overview');
}


if (isset($_GET['action'])) {
    switch ($_GET['action']) {
    case 'delete_db':
      if (! has_mysql_database($_GET['db'])) {
          system_failure('Ungültige Datenbank');
      }
      $sure = user_is_sure();
      if ($sure === null) {
          are_you_sure("action=delete_db&db={$_GET['db']}", "Möchten Sie die Datenbank »{$_GET['db']}« wirklich löschen?");
      } elseif ($sure === true) {
          delete_mysql_database($_GET['db']);
          redirect('overview');
      } elseif ($sure === false) {
          redirect('overview');
      }
      break;
    case 'delete_user':
      if (! has_mysql_user($_GET['user'])) {
          system_failure('Ungültiger Benutzer');
      }
      $sure = user_is_sure();
      if ($sure === null) {
          are_you_sure("action=delete_user&user={$_GET['user']}", "Möchten Sie den Benutzer »{$_GET['user']}« wirklich löschen?");
      } elseif ($sure === true) {
          delete_mysql_account($_GET['user']);
          redirect('overview');
      } elseif ($sure === false) {
          redirect('overview');
      }
      break;
    case 'change_pw':
      check_form_token('mysql_databases');
      $check = strong_password($_POST['newpass']);
      if ($check !== true) {
          system_failure('Das Passwort ist nicht sicher genug.');
      }
      set_mysql_password($_POST['mysql_username'], $_POST['newpass']);
      redirect('overview');
      break;
    default:
      system_failure("Diese Funktion scheint noch nicht eingebaut zu sein!");
  }
}

$dbs = get_mysql_databases($_SESSION['userinfo']['uid']);
$users = get_mysql_accounts($_SESSION['userinfo']['uid']);

if (isset($_POST['accesseditor'])) {
    check_form_token('mysql_databases');

    foreach ($dbs as $db) {
        $db = $db['name'];
        foreach ($users as $user) {
            $user = $user['username'];
            if (! isset($_POST['access'][$db])) {
                set_mysql_access($db, $user, false);
            } else {
                set_mysql_access($db, $user, in_array($user, $_POST['access'][$db]));
            }
        }
    }
    $mysql_access = null;
}

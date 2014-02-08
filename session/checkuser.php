<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2014 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/base.php');
require_once('inc/debug.php');
require_once('inc/error.php');


define('ROLE_ANONYMOUS', 0);
define('ROLE_MAILACCOUNT', 1);
define('ROLE_VMAIL_ACCOUNT', 2);
define('ROLE_SYSTEMUSER', 4);
define('ROLE_CUSTOMER', 8);
define('ROLE_SYSADMIN', 16);
define('ROLE_SUBUSER', 32);


// Gibt die Rolle aus, wenn das Passwort stimmt

function find_role($login, $password, $i_am_admin = False)
{
  // Domain-Admin?  <not implemented>
  // System-User?
  $uid = (int) $login;
  if ($uid == 0)
    $uid = NULL;
  $result = db_query("SELECT username, passwort AS password, kundenaccount AS `primary`, status, ((SELECT acc.uid FROM system.v_useraccounts AS acc LEFT JOIN system.gruppenzugehoerigkeit USING (uid) LEFT JOIN system.gruppen AS g ON (g.gid=gruppenzugehoerigkeit.gid) WHERE g.name='admin' AND acc.uid=u.uid) IS NOT NULL) AS admin FROM system.v_useraccounts AS u LEFT JOIN system.passwoerter USING(uid) WHERE u.uid=:uid OR username=:login LIMIT 1;", array(":uid" => $uid, ":login" => $login));
  if (@$result->rowCount() > 0)
  {
    $entry = $result->fetch(PDO::FETCH_OBJ);
    if (strcasecmp($entry->username, $login) == 0 && $entry->username != $login) {
      // MySQL matched (warum auch immer) ohne Beachtung der Schreibweise. Wir wollen aber case-sensitive sein.
      logger(LOG_WARNING, "session/checkuser", "login", "denying login to wrong cased username »{$login}«.");
      warning('Beachten Sie bei der Eingabe Ihrer Zugangsdaten bitte die Groß- und Kleinschreibung.');
      return NULL;  
    }
    $db_password = $entry->password;
    $hash = crypt($password, $db_password);
    if (($entry->status == 0 && $hash == $db_password) || $i_am_admin)
    {
      $role = ROLE_SYSTEMUSER;
      if ($entry->primary)
        $role = $role | ROLE_CUSTOMER;
      if ($entry->admin)
        $role = $role | ROLE_SYSADMIN;
      logger(LOG_INFO, "session/checkuser", "login", "logged in systemuser »{$login}«.");
      return $role;
    }
    logger(LOG_WARNING, "session/checkuser", "login", "wrong password for existing useraccount »{$login}«.");
  } else {
    logger(LOG_WARNING, "session/checkuser", "login", "did not find useraccount »{$login}«. trying other roles...");
  }

  // Customer?
  $customerno = (int) $login;
  $pass = sha1($password);
  $result = db_query("SELECT passwort AS password FROM kundendaten.kunden WHERE status=0 AND id=:customerno AND passwort=:pass", array(":customerno" => $customerno, ":pass" => $pass));
  if ($i_am_admin)
    $result = db_query("SELECT passwort AS password FROM kundendaten.kunden WHERE status=0 AND id=?", array($customerno));
  if (@$result->rowCount() > 0)
  {
    return ROLE_CUSTOMER;
  }

  // Sub-User

  $result = db_query("SELECT password FROM system.subusers WHERE username=?", array($login));
  if (@$result->rowCount() > 0)
  {
    $entry = $result->fetch(PDO::FETCH_OBJ);
    $db_password = $entry->password;
    // SHA1 für alte Subuser (kaylee), SHA256 für neue Subuser
    if (hash("sha1", $password) == $db_password || hash("sha256", $password) == $db_password || $i_am_admin)
    {
      logger(LOG_INFO, "session/checkuser", "login", "logged in virtual subuser »{$login}«.");
      return ROLE_SUBUSER;
    }
    logger(LOG_WARNING, "session/checkuser", "login", "wrong password for existing subuser »{$login}«.");
  }


  // Mail-Account
  $account = $login;
  if (! strstr($account, '@')) {
    $account .= '@'.config('masterdomain');
  }
  if (!$i_am_admin && have_module('webmailtotp')) {
    require_once('modules/webmailtotp/include/totp.php');
    if (account_has_totp($account)) {
      if (check_webmail_password($account, $password)) {
        $_SESSION['totp_username'] = $account;
        $_SESSION['totp'] = True;
        show_page('webmailtotp-login');
        die();
      } else {
        return NULL;
      }
    }
  }
  $result = db_query("SELECT cryptpass FROM mail.courier_mailaccounts WHERE account=?", array($account));
  if (@$result->rowCount() > 0)
  {
    $entry = $result->fetch(PDO::FETCH_OBJ);
    $db_password = $entry->cryptpass;
    $hash = crypt($password, $db_password);
    if ($hash == $db_password || $i_am_admin)
    {
      logger(LOG_INFO, "session/checkuser", "login", "logged in e-mail-account »{$account}«.");
      return ROLE_MAILACCOUNT;
    }
    logger(LOG_WARNING, "session/checkuser", "login", "wrong password for existing e-mail-account »{$account}«.");
  }
  
  // virtueller Mail-Account
  $account = $login;
  $result = db_query("SELECT cryptpass FROM mail.courier_virtual_accounts WHERE account=?", array($account));
  if (@$result->rowCount() > 0)
  {
    $entry = $result->fetch(PDO::FETCH_OBJ);
    $db_password = $entry->cryptpass;
    $hash = crypt($password, $db_password);
    if ($hash == $db_password || $i_am_admin)
    {
      logger(LOG_INFO, "session/checkuser", "login", "logged in virtual e-mail-account »{$account}«.");
      return ROLE_VMAIL_ACCOUNT;
    }
    logger(LOG_WARNING, "session/checkuser", "login", "wrong password for existing virtual e-mail-account »{$account}«.");
  }
  


  // Nothing?
  return NULL;
}


function get_customer_info($customer)
{
  if (! $_SESSION['role'] & ROLE_CUSTOMER)
    return array();
  $ret = array();
  $customerno = (int) $customer;
  if ($customerno != 0)
  {
    DEBUG('Looking up customerinfo for customer no. '.$customerno);
    $result = db_query("SELECT id, anrede, firma, CONCAT_WS(' ', vorname, nachname) AS name, COALESCE(email,email_rechnung,email_extern) AS email FROM kundendaten.kunden WHERE id=?", array($customerno));
  }
  else
  {
    $username = $customer;
    DEBUG('looking up customer info for username '.$username);
    $result = db_query("SELECT id, anrede, firma, CONCAT_WS(' ', vorname, nachname) AS name, COALESCE(email,email_rechnung,email_extern) AS email FROM kundendaten.kunden AS k JOIN system.v_useraccounts AS u ON (u.kunde=k.id) WHERE u.username=?", array($username));
  }
  if (@$result->rowCount() == 0)
    system_failure("Konnte Kundendaten nicht auslesen!");
  $data = $result->fetch();
  DEBUG($data);
  $ret['customerno'] = $data['id'];
  $ret['title'] = $data['anrede'];
  $ret['company'] = $data['firma'];
  $ret['name'] = $data['name'];
  $ret['email'] = $data['email'];
  
  return $ret;
}


function get_subuser_info($username)
{
  $result = db_query("SELECT uid, modules FROM system.subusers WHERE username=?", array($username));
  if ($result->rowCount() < 1)
  {
    logger(LOG_ERR, "session/checkuser", "login", "error reading subuser's data: »{$username}«");
    system_failure('Das Auslesen Ihrer Benutzerdaten ist fehlgeschlagen. Bitte melden Sie dies einem Administrator');
  }
  $data = $result->fetch();
  $userinfo = get_user_info($data['uid']);
  $userinfo['modules'] = $data['modules'];
  return $userinfo;
}


function get_user_info($username)
{
  $result = db_query("SELECT kunde AS customerno, username, uid, homedir, name, server
                      FROM system.v_useraccounts WHERE username=:username OR uid=:username", array(":username" => $username));
  if ($result->rowCount() < 1)
  {
    logger(LOG_ERR, "session/checkuser", "login", "error reading user's data: »{$username}«");
    system_failure('Das Auslesen Ihrer Benutzerdaten ist fehlgeschlagen. Bitte melden Sie dies einem Administrator');
  }
  $val = @$result->fetch(PDO::FETCH_OBJ);
  return array(
          'username'      => $val->username,
          'customerno'    => $val->customerno,
          'uid'           => $val->uid,
          'homedir'       => $val->homedir,
          'server'        => $val->server,
          'name'          => $val->name,
          );
}

function set_customer_verified($customerno)
{
  $customerno = (int) $customerno;
  db_query("UPDATE kundendaten.kunden SET status=0 WHERE id=?", array($customerno));
  logger(LOG_INFO, "session/checkuser", "register", "set customer's status to 0.");
}

function set_customer_lastlogin($customerno)
{
  $customerno = (int) $customerno;
  db_query("UPDATE kundendaten.kunden SET lastlogin=NOW() WHERE id=?", array($customerno));
}

function set_customer_password($customerno, $newpass)
{
  $customerno = (int) $customerno;
  $newpass = sha1($newpass);
  db_query("UPDATE kundendaten.kunden SET passwort=:newpass WHERE id=:customerno", array(":newpass" => $newpass, ":customerno" => $customerno));
  logger(LOG_INFO, "session/checkuser", "pwchange", "changed customer's password.");
}

function set_subuser_password($subuser, $newpass)
{
  $args = array(":subuser" => $subuser,
                ":uid" => (int) $_SESSION['userinfo']['uid'],
                ":newpass" => sha1($newpass));
  db_query("UPDATE system.subusers SET password=:newpass WHERE username=:subuser AND uid=:uid", $args);
  logger(LOG_INFO, "session/checkuser", "pwchange", "changed subuser's password.");
}

function set_systemuser_password($uid, $newpass)
{
  $uid = (int) $uid;
  require_once('inc/base.php');
  if (defined("CRYPT_SHA512") && CRYPT_SHA512 == 1)
  {
    $rounds = rand(1000, 5000);
    $salt = "rounds=".$rounds."$".random_string(8);
    $newpass = crypt($newpass, "\$6\${$salt}\$");
  }
  else
  {
    $salt = random_string(8);
    $newpass = crypt($newpass, "\$1\${$salt}\$");
  }
  db_query("UPDATE system.passwoerter SET passwort=:newpass WHERE uid=:uid", array(":newpass" => $newpass, ":uid" => $uid));
  logger(LOG_INFO, "session/checkuser", "pwchange", "changed user's password.");
}


function user_for_mailaccount($account) 
{
  $result = db_query("SELECT uid FROM mail.courier_mailaccounts WHERE account=?", array($account));
  if ($result->rowCount() != 1) {
    system_failure('Diese Adresse ist herrenlos?!');
  }
  $tmp = $result->fetch();
  return $tmp['uid'];
}

function user_for_vmail_account($account)
{
  $result = db_query("SELECT useraccount FROM mail.v_vmail_accounts WHERE CONCAT_WS('@', local, domainname)=?", array($account));
  if ($result->rowCount() != 1) {
    system_failure('Diese Adresse ist herrenlos?!');
  }
  $tmp = $result->fetch();
  return $tmp['useraccount'];
}


function setup_session($role, $useridentity)
{
  session_regenerate_id();
  $_SESSION['role'] = $role;
  if ($role & ROLE_SUBUSER)
  {
    DEBUG("We are a sub-user");
    $info = get_subuser_info($useridentity);
    $_SESSION['userinfo'] = $info;
    $_SESSION['restrict_modules'] = explode(',', $info['modules']);
    $_SESSION['role'] = ROLE_SYSTEMUSER | ROLE_SUBUSER;
    $_SESSION['subuser'] = $useridentity;
    $data = db_query("SELECT kundenaccount FROM system.useraccounts WHERE username=?", array($info['username']));
    if ($entry = $data->fetch()) {
      if ($entry['kundenaccount'] == 1) {
        $customer = get_customer_info($_SESSION['userinfo']['username']);
        $_SESSION['customerinfo'] = $customer;
        $_SESSION['role'] = ROLE_SYSTEMUSER | ROLE_CUSTOMER | ROLE_SUBUSER;
      }
    }
    logger(LOG_INFO, "session/start", "login", "logged in user »{$info['username']}«");
  }
  if ($role & ROLE_SYSTEMUSER)
  {
    DEBUG("We are system user");
    $info = get_user_info($useridentity);
    $_SESSION['userinfo'] = $info;
    logger(LOG_INFO, "session/start", "login", "logged in user »{$info['username']}«");
    $useridentity = $info['customerno'];
  }
  if ($role & ROLE_CUSTOMER)
  {
    $info = get_customer_info($useridentity);
    $_SESSION['customerinfo'] = $info;
    if (!isset($_SESSION['admin_user'])) {
      set_customer_lastlogin($info['customerno']);
    }
    logger(LOG_INFO, "session/start", "login", "logged in customer no »{$info['customerno']}«");
  }
  if ($role & ROLE_MAILACCOUNT)
  {
    $id = $useridentity;
    if (! strstr($id, '@'))
      $id .= '@'.config('masterdomain');
    $uid = user_for_mailaccount($id);
    $_SESSION['mailaccount'] = $id;
    $_SESSION['userinfo'] = get_user_info($uid);
    DEBUG("We are mailaccount: {$_SESSION['mailaccount']}");
  }
  if ($role & ROLE_VMAIL_ACCOUNT)
  {
    $id = $useridentity;
    $uid = user_for_vmail_account($id);
    $_SESSION['mailaccount'] = $id;
    $_SESSION['userinfo'] = get_user_info($uid);
    DEBUG("We are virtual mailaccount: {$_SESSION['mailaccount']}");
  }

}

?>

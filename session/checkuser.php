<?php

require_once('inc/base.php');
require_once('inc/debug.php');
require_once('inc/error.php');

require_once('inc/db_connect.php');

define('ROLE_ANONYMOUS', 0);
define('ROLE_MAILACCOUNT', 1);
define('ROLE_VMAIL_ACCOUNT', 2);
define('ROLE_SYSTEMUSER', 4);
define('ROLE_CUSTOMER', 8);
define('ROLE_SYSADMIN', 16);


// Gibt die Rolle aus, wenn das Passwort stimmt

function find_role($login, $password, $i_am_admin = False)
{
  $login = mysql_real_escape_string($login);
  // Domain-Admin?  <not implemented>
  // System-User?
  $uid = (int) $login;
  if ($uid == 0)
    $uid = 'NULL';
  $result = db_query("SELECT passwort AS password, kundenaccount AS `primary`, ((SELECT acc.uid FROM system.v_useraccounts AS acc LEFT JOIN system.gruppenzugehoerigkeit USING (uid) LEFT JOIN system.gruppen AS g ON (g.gid=gruppenzugehoerigkeit.gid) WHERE g.name='admin' AND acc.uid=u.uid) IS NOT NULL) AS admin FROM system.v_useraccounts AS u LEFT JOIN system.passwoerter USING(uid) WHERE u.uid={$uid} OR username='{$login}' LIMIT 1;");
  if (@mysql_num_rows($result) > 0)
  {
    $entry = mysql_fetch_object($result);
    $db_password = $entry->password;
    $hash = crypt($password, $db_password);
    if ($hash == $db_password || $i_am_admin)
    {
      $role = ROLE_SYSTEMUSER;
      if ($entry->primary)
        $role = $role | ROLE_CUSTOMER;
      if ($entry->admin)
        $role = $role | ROLE_SYSADMIN;
      logger("session/checkuser", "login", "logged in systemuser »{$login}«.");
      return $role;
    }
    logger("session/checkuser", "login", "wrong password for existing useraccount »{$login}«.");
  } else {
    logger("session/checkuser", "login", "did not find useraccount »{$login}«. trying other roles...");
  }

  // Customer?
  $customerno = (int) $login;
  $pass = sha1($password);
  $result = db_query("SELECT passwort AS password FROM kundendaten.kunden WHERE status=0 AND id={$customerno} AND passwort='{$pass}';");
  if ($i_am_admin)
    $result = db_query("SELECT passwort AS password FROM kundendaten.kunden WHERE status=0 AND id={$customerno}");
  if (@mysql_num_rows($result) > 0)
  {
    return ROLE_CUSTOMER;
  }

  // Mail-Account
  $account = $login;
  if (! strstr($account, '@')) {
    $account .= '@schokokeks.org';
  }
  $result = db_query("SELECT cryptpass FROM mail.courier_mailaccounts WHERE account='{$account}' LIMIT 1;");
  if (@mysql_num_rows($result) > 0)
  {
    $entry = mysql_fetch_object($result);
    $db_password = $entry->cryptpass;
    $hash = crypt($password, $db_password);
    if ($hash == $db_password || $i_am_admin)
    {
      logger("session/checkuser", "login", "logged in e-mail-account »{$account}«.");
      return ROLE_MAILACCOUNT;
    }
    logger("session/checkuser", "login", "wrong password for existing e-mail-account »{$account}«.");
  }
  
  // virtueller Mail-Account
  $account = $login;
  $result = db_query("SELECT cryptpass FROM mail.courier_virtual_accounts WHERE account='{$account}' LIMIT 1;");
  if (@mysql_num_rows($result) > 0)
  {
    $entry = mysql_fetch_object($result);
    $db_password = $entry->cryptpass;
    $hash = crypt($password, $db_password);
    if ($hash == $db_password || $i_am_admin)
    {
      logger("session/checkuser", "login", "logged in virtual e-mail-account »{$account}«.");
      return ROLE_VMAIL_ACCOUNT;
    }
    logger("session/checkuser", "login", "wrong password for existing virtual e-mail-account »{$account}«.");
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
    $result = db_query("SELECT id, anrede, firma, CONCAT_WS(' ', vorname, nachname) AS name FROM kundendaten.kunden WHERE id={$customerno} LIMIT 1;");
  }
  else
  {
    $username = mysql_real_escape_string($customer);
    DEBUG('looking up customer info for username '.$username);
    $result = db_query("SELECT id, anrede, firma, CONCAT_WS(' ', vorname, nachname) AS name FROM kundendaten.kunden AS k JOIN system.v_useraccounts AS u ON (u.kunde=k.id) WHERE u.username='{$username}'");
  }
  if (@mysql_num_rows($result) == 0)
    system_failure("Konnte Kundendaten nicht auslesen!");
  $data = mysql_fetch_object($result);

  $ret['customerno'] = $data->id;
  $ret['title'] = $data->anrede;
  $ret['company'] = $data->firma;
  $ret['name'] = $data->name;
  
  return $ret;
}


function get_customer_email($customerno)
{
  $customerno = (int) $customerno;
  $result = db_query("SELECT wert FROM kundendaten.kundenkontakt WHERE kundennr={$customerno} AND typ='email' LIMIT 1;");
  if (@mysql_num_rows($result) == 0)
    system_failure("Konnte keine E-Mail-Adresse finden!");
  return mysql_fetch_object($result)->wert;
}



function get_user_info($username)
{
  $username = mysql_real_escape_string($username);
  $result = db_query("SELECT kunde AS customerno, username, uid, homedir, name
                      FROM system.v_useraccounts WHERE username='{$username}' OR uid='{$username}' LIMIT 1");
  if (mysql_num_rows($result) < 1)
  {
    logger("session/checkuser", "login", "error reading user's data: »{$username}«");
    system_failure('Das Auslesen Ihrer Benutzerdaten ist fehlgeschlagen. Bitte melden Sie dies einem Administrator');
  }
  $val = @mysql_fetch_object($result);
  return array(
          'username'      => $val->username,
          'customerno'    => $val->customerno,
          'uid'           => $val->uid,
          'homedir'       => $val->homedir,
          'name'          => $val->name,
          );
}

function set_customer_verified($customerno)
{
  $customerno = (int) $customerno;
  db_query("UPDATE kundendaten.kunden SET status=0 WHERE id={$customerno};");
  logger("session/checkuser", "register", "set customer's status to 0.");
}

function set_customer_lastlogin($customerno)
{
  $customerno = (int) $customerno;
  db_query("UPDATE kundendaten.kunden SET lastlogin=NOW() WHERE id={$customerno};");
}

function set_customer_password($customerno, $newpass)
{
  $customerno = (int) $customerno;
  $newpass = sha1($newpass);
  db_query("UPDATE kundendaten.kunden SET passwort='$newpass' WHERE id='".$customerno."' LIMIT 1");
  logger("session/checkuser", "pwchange", "changed customer's password.");
}


function set_systemuser_password($uid, $newpass)
{
  $uid = (int) $uid;
  require_once('inc/base.php');
  $salt = random_string(8);
  $newpass = crypt($newpass, "\$1\${$salt}\$");
  db_query("UPDATE system.passwoerter SET passwort='$newpass' WHERE uid='".$uid."' LIMIT 1");
  logger("session/checkuser", "pwchange", "changed user's password.");
}


function setup_session($role, $useridentity)
{
  session_regenerate_id();
  $_SESSION['role'] = $role;
  if ($role & ROLE_SYSTEMUSER)
  {
    DEBUG("We are system user");
    $info = get_user_info($useridentity);
    $_SESSION['userinfo'] = $info;
    logger("session/start", "login", "logged in user »{$info['username']}«");
    $useridentity = $info['customerno'];
  }
  if ($role & ROLE_CUSTOMER)
  {
    $info = get_customer_info($useridentity);
    $_SESSION['customerinfo'] = $info;
    set_customer_lastlogin($info['customerno']);
    logger("session/start", "login", "logged in customer no »{$info['customerno']}«");
  }
  if ($role & ROLE_MAILACCOUNT)
  {
    $id = $useridentity;
    if (! strstr($id, '@'))
      $id .= '@schokokeks.org';
    $_SESSION['mailaccount'] = $id;
    DEBUG("We are mailaccount: {$_SESSION['mailaccount']}");
  }
  if ($role & ROLE_VMAIL_ACCOUNT)
  {
    $id = $useridentity;
    $_SESSION['mailaccount'] = $id;
    DEBUG("We are virtual mailaccount: {$_SESSION['mailaccount']}");
  }

}

?>

<?php

require_once('inc/debug.php');
require_once('inc/error.php');

require_once('inc/db_connect.php');

define('ROLE_ANONYMOUS', 0);
define('ROLE_DOMAINADMIN', 1);
define('ROLE_SYSTEMUSER', 2);
define('ROLE_CUSTOMER', 3);
define('ROLE_SYSADMIN', 4);


// Gibt die Rolle aus, wenn das Passwort stimmt

function find_role($login, $password)
{
  $login = mysql_real_escape_string($login);
  // Domain-Admin?  <not implemented>
  // System-User?
  $uid = (int) $login;
  if ($uid == 0)
    $uid = 'NULL';
  $result = @mysql_query("SELECT passwort AS password FROM system.v_useraccounts LEFT JOIN system.passwoerter USING (uid) WHERE uid={$uid} OR username='{$login}' LIMIT 1;");
  if (mysql_error())
    system_failure(mysql_error());
  if (@mysql_num_rows($result) > 0)
  {
    $db_password = mysql_fetch_object($result)->password;
    $hash = crypt($password, $db_password);
    if ($hash == $db_password)
      return ROLE_SYSTEMUSER;
  }

  // Customer?
  $customerno = (int) $login;
  $pass = sha1($password);
  $result = @mysql_query("SELECT passwort AS password FROM kundendaten.kunden WHERE status=0 AND id={$customerno} AND passwort='{$pass}';");
  if (mysql_error())
    system_failure(mysql_error());
  if (@mysql_num_rows($result) > 0)
  {
    return ROLE_CUSTOMER;
  }

  // Nothing?
  return NULL;
}


function get_customer_info($customerno)
{
  $ret = array();
  $customerno = (int) $customerno;
  $query = "SELECT id, anrede, firma, CONCAT_WS(' ', vorname, nachname) AS name FROM kundendaten.kunden WHERE id={$customerno} LIMIT 1;";
  $result = @mysql_query($query);
  if (mysql_error())
    system_failure(mysql_error());
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
  $query = "SELECT wert FROM kundendaten.kundenkontakt WHERE kundennr={$customerno} AND typ='email' LIMIT 1;";
  $result = @mysql_query($query);
  if (mysql_error())
    system_failure(mysql_error());
  if (@mysql_num_rows($result) == 0)
    system_failure("Konnte keine E-Mail-Adresse finden!");
  return mysql_fetch_object($result)->wert;
}



function get_user_info($username)
{
  $username = mysql_real_escape_string($username);
  $query = "SELECT kunde AS customerno, username, uid, homedir, name
            FROM system.v_useraccounts WHERE username='{$username}' OR uid='{$username}' LIMIT 1";
  $result = @mysql_query($query);
  if (mysql_error())
    system_failure('Beim Datenbankzugriff ist ein Fehler aufgetreten. Sollte dies wiederholt vorkommen, senden Sie bitte die Fehlermeldung ('.mysql_error().') an einen Administrator.');
  if (mysql_num_rows($result) < 1)
    system_failure('Das Auslesen Ihrer Benutzerdaten ist fehlgeschlagen. Bitte melden Sie dies einem Administrator');
  $val = @mysql_fetch_object($result);
  return array(
          'username'      => $val->username,
          'customerno'    => $val->customerno,
          'uid'           => $val->uid,
          'homedir'       => $val->homedir,
          'name'          => $val->name,
          );
}

function set_customer_password($customerno, $newpass)
{
  $customerno = (int) $customerno;
  $newpass = sha1($newpass);
  $query = "UPDATE kundendaten.kunden SET passwort='$newpass' WHERE id='".$customerno."' LIMIT 1";
  @mysql_query($query);
  if (mysql_error())
    system_failure('Beim Datenbankzugriff ist ein Fehler aufgetreten. Sollte dies wiederholt vorkommen, senden Sie bitte die Fehlermeldung ('.mysql_error().') an einen Administrator.');

}


function set_systemuser_password($uid, $newpass)
{
  $uid = (int) $uid;
  require_once('inc/base.php');
  $salt = random_string(8);
  $newpass = crypt($newpass, "\$1\${$salt}\$");
  $query = "UPDATE system.passwoerter SET passwort='$newpass' WHERE uid='".$uid."' LIMIT 1";
  @mysql_query($query);
  if (mysql_error())
    system_failure('Beim Datenbankzugriff ist ein Fehler aufgetreten. Sollte dies wiederholt vorkommen, senden Sie bitte die Fehlermeldung ('.mysql_error().') an einen Administrator.');
}


/*
function save_userdata($arr)
{
  global $user;

  $rules = array();

  if (isset($arr['email'])) {
    if (!preg_match('/[a-zA-Z0-9=+._%@-]+@[a-zA-Z0-9.-]+\.[a-z]{2,6}/', $arr['email'])) {
      input_error('Ihre eMail-Adresse enth&auml;lt Syntax-Fehler!');
      return false;
    }
    array_push($rules, "`email`='".$arr['email']."'");
  }

  if (isset($arr['emergency_email'])) {
    if ($arr['emergency_email'] == '') {
      warning("Sie k&ouml;nnen zwar Ihre Notfall-eMail-Adresse l&ouml;schen, allerdings werden Sie dann bei St&ouml;rungen <strong>nicht</strong> mehr benachrichtigt!");
    }
    elseif (!preg_match('/[a-zA-Z0-9=+._%@-]+@[a-zA-Z0-9.-]+\.[a-z]{2,6}/', $arr['emergency_email'])) {
      input_error('Ihre eMail-Adresse enth&auml;lt Syntax-Fehler!');
      return false;
    }
    array_push($rules, "`emergency_email`='".$arr['emergency_email']."'");
  }

  $query = "UPDATE customers SET ".implode(',', $rules)." WHERE id='".$user['customerno']."'";
  mysql_query($query);
  if (mysql_error())
    system_failure('Beim Datenbankzugriff ist ein Fehler aufgetreten. Sollte dies wiederholt vorkommen, senden Sie bitte die Fehlermeldung ('.mysql_error().') an einen Administrator.');
  return true;
}

*/

?>

<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2013 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('session/checkuser.php');

function user_customer_match($cust, $user)
{
  $customerno = (int) $cust;
  $username = db_escape_string($user);
  $result = db_query("SELECT uid FROM system.useraccounts WHERE kunde={$customerno} AND username='{$username}' AND kundenaccount=1;");
  if ($result->rowCount() > 0)
    return true;
  return false;
}



function customer_has_email($customerno, $email)
{
  $customerno = (int) $customerno;
  $email = db_escape_string($email);
  $result = db_query("SELECT NULL FROM kundendaten.kunden WHERE id=".$customerno." AND (email='{$email}' OR email_extern='{$email}' OR email_rechnung='{$email}');");
  return ($result->rowCount() > 0);
}


function validate_token($customerno, $token)
{
  expire_tokens();
  $customerno = (int) $customerno;
  $token = db_escape_string($token);
  $result = db_query("SELECT NULL FROM kundendaten.kunden WHERE id={$customerno} AND token='{$token}';");
  return ($result->rowCount() > 0);
}


function get_uid_for_token($token) 
{
  expire_tokens();
  $token = db_escape_string($token);
  $result = db_query("SELECT uid FROM system.usertoken WHERE token='{$token}';");
  if ($result->rowCount() == 0) {
    return NULL;
  }
  $data = $result->fetch();
  return $data['uid'];  
}

function get_username_for_uid($uid) 
{
  $uid = (int) $uid;
  $result = db_query("SELECT username FROM system.useraccounts WHERE uid={$uid}");
  if ($result->rowCount() != 1) {
    system_failure("Unexpected number of users with this uid (!= 1)!");
  }
  $item = $result->fetch();
  return $item['username'];
}

function validate_uid_token($uid, $token)
{
  expire_tokens();
  $uid = (int) $uid;
  $token = db_escape_string($token);
  $result = db_query("SELECT NULL FROM system.usertoken WHERE uid={$uid} AND token='{$token}';");
  return ($result->rowCount() > 0);
}


function expire_tokens()
{
  $expire = "1 DAY";
  db_query("UPDATE kundendaten.kunden SET token=NULL, token_create=NULL WHERE token_create < NOW() - INTERVAL {$expire};");
  db_query("DELETE FROM system.usertoken WHERE expire < NOW();");
}

function invalidate_customer_token($customerno)
{
  $customerno = (int) $customerno;
  db_query("UPDATE kundendaten.kunden SET token=NULL, token_create=NULL WHERE id={$customerno} LIMIT 1;");
}
 
function invalidate_systemuser_token($uid)
{
  $uid = (int) $uid;
  db_query("DELETE FROM system.usertoken WHERE uid={$uid} LIMIT 1;");
}
 
function create_token($username)
{
  $username = db_escape_string($username);
  expire_tokens();
  $result = db_query("SELECT uid FROM system.useraccounts WHERE username='{$username}'");
  $uid = (int) $result->fetch()['uid'];
  
  $result = db_query("SELECT created FROM system.usertoken WHERE uid={$uid}");
  if ($result->rowCount() > 0) {
    system_failure("Für Ihr Benutzerkonto ist bereits eine Passwort-Erinnerung versendet worden. Bitte wenden Sie sich an den Support wenn Sie diese nicht erhalten haben.");
  }
  
  $token = random_string(16);
  db_query("INSERT INTO system.usertoken VALUES ({$uid}, NOW(), NOW() + INTERVAL 1 DAY, '{$token}')");
  return true;
}


function emailaddress_for_user($username)
{
  $username = db_escape_string($username);
  $result = db_query("SELECT k.email FROM kundendaten.kunden AS k INNER JOIN system.useraccounts AS u ON (u.kunde=k.id) WHERE u.username='{$username}'");
  $data = $result->fetch();
  return $data['email'];
}


function get_customer_token($customerno)
{
  $customerno = (int) $customerno;
  expire_tokens();
  $result = db_query("SELECT token FROM kundendaten.kunden WHERE id={$customerno} AND token IS NOT NULL;");
  if ($result->rowCount() < 1)
    system_failure("Kann das Token nicht auslesen!");
  return $result->fetch(PDO::FETCH_OBJ)->token;
}


function get_user_token($username) 
{
  $username = db_escape_string($username);
  $result = db_query("SELECT token FROM system.usertoken AS t INNER JOIN system.useraccounts AS u USING (uid) WHERE username='{$username}'");
  $tmp = $result->fetch();
  return $tmp['token'];
}

?>

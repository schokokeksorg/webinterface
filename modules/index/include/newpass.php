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

require_once('inc/db.php');
require_once('session/checkuser.php');

function customer_has_email($customerno, $email)
{
  $customerno = (int) $customerno;
  $email = DB::escape($email);
  $result = DB::query("SELECT NULL FROM kundendaten.kunden WHERE id=".$customerno." AND (email='{$email}' OR email_extern='{$email}' OR email_rechnung='{$email}');");
  return ($result->num_rows > 0);
}


function validate_token($customerno, $token)
{
  expire_tokens();
  $customerno = (int) $customerno;
  $token = DB::escape($token);
  $result = DB::query("SELECT NULL FROM kundendaten.kunden WHERE id={$customerno} AND token='{$token}';");
  return ($result->num_rows > 0);
}


function get_uid_for_token($token) 
{
  expire_tokens();
  $token = DB::escape($token);
  $result = DB::query("SELECT uid FROM system.usertoken WHERE token='{$token}';");
  if ($result->num_rows == 0) {
    return NULL;
  }
  $data = $result->fetch_assoc();
  return $data['uid'];  
}

function get_username_for_uid($uid) 
{
  $uid = (int) $uid;
  $result = DB::query("SELECT username FROM system.useraccounts WHERE uid={$uid}");
  if ($result->num_rows != 1) {
    system_failure("Unexpected number of users with this uid (!= 1)!");
  }
  $item = $result->fetch_assoc();
  return $item['username'];
}

function validate_uid_token($uid, $token)
{
  expire_tokens();
  $uid = (int) $uid;
  $token = DB::escape($token);
  $result = DB::query("SELECT NULL FROM system.usertoken WHERE uid={$uid} AND token='{$token}';");
  return ($result->num_rows > 0);
}


function expire_tokens()
{
  $expire = "1 DAY";
  DB::query("UPDATE kundendaten.kunden SET token=NULL, token_create=NULL WHERE token_create < NOW() - INTERVAL {$expire};");
  DB::query("DELETE FROM system.usertoken WHERE expire < NOW();");
}

function invalidate_customer_token($customerno)
{
  $customerno = (int) $customerno;
  DB::query("UPDATE kundendaten.kunden SET token=NULL, token_create=NULL WHERE id={$customerno} LIMIT 1;");
}
 
function invalidate_systemuser_token($uid)
{
  $uid = (int) $uid;
  DB::query("DELETE FROM system.usertoken WHERE uid={$uid} LIMIT 1;");
}
 
function create_token($customerno)
{
  $customerno = (int) $customerno;
  expire_tokens();
  $result = DB::query("SELECT token_create FROM kundendaten.kunden WHERE id={$customerno} AND token_create IS NOT NULL;");
  if ($result->num_rows > 0)
  {
    $res = $result->fetch_object()->token_create;
    input_error("Sie haben diese Funktion kürzlich erst benutzt, an Ihre E-Mail-Adresse wurde bereits am {$res} eine Nachricht verschickt. Sie können diese Funktion erst nach Ablauf von 24 Stunden erneut benutzen.");
    return false;
  }
  $token = random_string(10);
  DB::query("UPDATE kundendaten.kunden SET token='{$token}', token_create=now() WHERE id={$customerno} LIMIT 1;");
  return true;
}


function get_customer_token($customerno)
{
  $customerno = (int) $customerno;
  expire_tokens();
  $result = DB::query("SELECT token FROM kundendaten.kunden WHERE id={$customerno} AND token IS NOT NULL;");
  if ($result->num_rows < 1)
    system_failure("Kann das Token nicht auslesen!");
  return $result->fetch_object()->token;
}


?>

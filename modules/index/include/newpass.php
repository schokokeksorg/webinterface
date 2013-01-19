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

require_once('inc/db_connect.php');
require_once('session/checkuser.php');

function customer_has_email($customerno, $email)
{
  $customerno = (int) $customerno;
  $email = mysql_real_escape_string($email);
  $result = db_query("SELECT NULL FROM kundendaten.kunden WHERE id=".$customerno." AND (email='{$email}' OR email_extern='{$email}' OR email_rechnung='{$email}');");
  return (mysql_num_rows($result) > 0);
}


function validate_token($customerno, $token)
{
  expire_tokens();
  $customerno = (int) $customerno;
  $token = mysql_real_escape_string($token);
  $result = db_query("SELECT NULL FROM kundendaten.kunden WHERE id={$customerno} AND token='{$token}';");
  return (mysql_num_rows($result) > 0);
}


function validate_uid_token($uid, $token)
{
  expire_tokens();
  $uid = (int) $uid;
  $token = mysql_real_escape_string($token);
  $result = db_query("SELECT NULL FROM system.usertoken WHERE uid={$uid} AND token='{$token}';");
  return (mysql_num_rows($result) > 0);
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
 
function create_token($customerno)
{
  $customerno = (int) $customerno;
  expire_tokens();
  $result = db_query("SELECT token_create FROM kundendaten.kunden WHERE id={$customerno} AND token_create IS NOT NULL;");
  if (mysql_num_rows($result) > 0)
  {
    $res = mysql_fetch_object($result)->token_create;
    input_error("Sie haben diese Funktion kürzlich erst benutzt, an Ihre E-Mail-Adresse wurde bereits am {$res} eine Nachricht verschickt. Sie können diese Funktion erst nach Ablauf von 24 Stunden erneut benutzen.");
    return false;
  }
  $token = random_string(10);
  db_query("UPDATE kundendaten.kunden SET token='{$token}', token_create=now() WHERE id={$customerno} LIMIT 1;");
  return true;
}


function get_customer_token($customerno)
{
  $customerno = (int) $customerno;
  expire_tokens();
  $result = db_query("SELECT token FROM kundendaten.kunden WHERE id={$customerno} AND token IS NOT NULL;");
  if (mysql_num_rows($result) < 1)
    system_failure("Kann das Token nicht auslesen!");
  return mysql_fetch_object($result)->token;
}


?>

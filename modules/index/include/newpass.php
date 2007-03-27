<?php

require_once('inc/db_connect.php');
require_once('session/checkuser.php');

function customer_has_email($customerno, $email)
{
  $customerno = (int) $customerno;
  $email = mysql_real_escape_string($email);
  $query = "SELECT NULL FROM kundendaten.kundenkontakt WHERE kundennr=".$customerno." AND wert='".$email."';";
  $result = @mysql_query($query);
  if (mysql_error())
    system_failure(mysql_error());
  return (mysql_num_rows($result) > 0);
}


function validate_token($customerno, $token)
{
  expire_tokens();
  $customerno = (int) $customerno;
  $token = mysql_real_escape_string($token);
  $result = @mysql_query("SELECT NULL FROM kundendaten.kunden WHERE id={$customerno} AND token='{$token}';");
  if (mysql_error())
    system_failure(mysql_error());
  return (mysql_num_rows($result) > 0);
}


function expire_tokens()
{
  $expire = "1 DAY";
  @mysql_query("UPDATE kundendaten.kunden SET token=NULL, token_create=NULL WHERE token_create < NOW() - INTERVAL {$expire};");
}

function invalidate_customer_token($customerno)
{
  $customerno = (int) $customerno;
  @mysql_query("UPDATE kundendaten.kunden SET token=NULL, token_create=NULL WHERE id={$customerno} LIMIT 1;");
}
 
function create_token($customerno)
{
  $customerno = (int) $customerno;
  expire_tokens();
  $result = @mysql_query("SELECT token_create FROM kundendaten.kunden WHERE id={$customerno} AND token_create IS NOT NULL;");
  if (mysql_num_rows($result) > 0)
  {
    $res = mysql_fetch_object($result)->token_create;
    input_error("Sie haben diese Funktion kürzlich erst benutzt, an Ihre E-Mail-Adresse wurde bereits am {$res} eine Nachricht verschickt. Sie können diese Funktion erst nach Ablauf von 24 Stunden erneut benutzen.");
    return false;
  }
  $token = random_string(10);
  $query = "UPDATE kundendaten.kunden SET token='{$token}', token_create=now() WHERE id={$customerno} LIMIT 1;";
  @mysql_query($query);
  if (mysql_error())
    system_failure(mysql_error());
  return true;
}


function get_customer_token($customerno)
{
  $customerno = (int) $customerno;
  expire_tokens();
  $result = @mysql_query("SELECT token FROM kundendaten.kunden WHERE id={$customerno} AND token IS NOT NULL;");
  if (mysql_error())
    system_failure(mysql_error());
  if (mysql_num_rows($result) < 1)
    system_failure("Kann das Token nicht auslesen!");
  return mysql_fetch_object($result)->token;
}


?>

<?php

require_once('inc/db_connect.php');

function customer_with_email($email)
{
  $email = mysql_real_escape_string($email);
  $result = db_query("SELECT kundennr FROM kundendaten.kundenkontakt WHERE wert='{$email}' LIMIT 1;");
  if (mysql_num_rows($result) == 0)
    return NULL;
  else
    return mysql_fetch_object($result)->kundennr;
}



function create_customer($data)
{

  if (customer_with_email($data['email']) !== NULL)
  {
    logger('modules/register/include/register.php', 'register', "Attempt to create customer with duplicate email »{$data['email']}«");
    return NULL;
  }

  $anrede = mysql_escape_string($data['anrede']);
  $firma = mysql_escape_string($data['firma']);
  $vorname = mysql_escape_string($data['vorname']);
  $nachname = mysql_escape_string($data['nachname']);
  $email = mysql_escape_string($data['email']);

  logger('modules/register/include/register.php', 'register', "Creating new account: {$anrede} / {$firma} / {$vorname} / {$nachname} / {$email}");
  
  $realname = maybe_null(chop($vorname.' '.$nachname));

  $anrede = maybe_null($anrede);
  $firma = maybe_null($firma);
  $vorname = maybe_null($vorname);
  $nachname = maybe_null($nachname);

  db_query("BEGIN");
  db_query("INSERT INTO kundendaten.kunden (firma, nachname, vorname, anrede) VALUES ({$firma}, {$nachname}, {$vorname}, {$anrede})");
  $customerno = mysql_insert_id();
  db_query("INSERT INTO kundendaten.kundenkontakt (typ, comment, wert, name, kundennr) VALUES ('email', 'extern', '{$email}', {$realname}, {$customerno})");
  db_query("COMMIT");
  return $customerno;

}


?>

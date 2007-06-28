<?php

require_once('inc/db_connect.php');
require_once('mail.php');

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
  db_query("INSERT INTO kundendaten.kunden (firma, nachname, vorname, anrede, erstellungsdatum) VALUES ({$firma}, {$nachname}, {$vorname}, {$anrede}, CURDATE())");
  $customerno = mysql_insert_id();
  db_query("INSERT INTO kundendaten.kundenkontakt (typ, comment, wert, name, kundennr) VALUES ('email', 'extern', '{$email}', {$realname}, {$customerno})");
  db_query("COMMIT");
  return $customerno;

}


function send_initial_customer_token($customerno)
{
  $customerno = (int) $customerno;
  $token = get_customer_token($customerno);
  $customer = get_customer_info($customerno);
  $email = get_customer_email($customerno);
  $anrede = "Sehr geehrte Damen und Herren";
  if ($customer['title'] == 'Herr')
    $anrede = "Sehr geehrter Herr {$customer['name']}";
  elseif ($customer['title'] == 'Frau')
    $anrede = "Sehr geehrte Frau {$customer['name']}";
  $msg = "{$anrede},

wir freuen uns, Sie bei schokokeks.org begrüßen zu dürfen.


Sie haben sich unter https://config.schokokeks.org/ als Kunde von 
schokokeks.org angemeldet. Diese E-Mail ist ein Zwischenschritt um 
Ihre E-Mail-Adresse zu überprüfen.

Um ein neues Passwort für Ihren Kunden-Zugang festzulegen, rufen 
Sie bitte die folgende Adresse auf:
 https://config.schokokeks.org/go/index/validate_token.php?customerno={$customer['customerno']}&token={$token}

Sollte Ihr E-Mail-Programm diesen Link nicht korrekt an den Browser
übertragen, rufen Sie bitte die Seite
 https://config.schokokeks.org/go/index/validate_token.php
auf und geben Sie die folgenden Daten ein:
 Kundennummer: {$customer['customerno']}
 Code:         {$token}

Diese Prozedur müssen Sie bis spätestens 24 Stunden nach Erhalt
dieser Nachricht durchführen, sonst verliert der Code seine
Gültigkeit.

Sofern Sie keinen Account bei schokokeks.org angemeldet haben, 
können Sie diese Nachricht ignorieren. 
";
  send_mail($email, "Willkommen bei schokokeks.org", $msg);
}


function welcome_customer($customerno)
{
  $customerno = (int) $customerno;
  $customer = get_customer_info($customerno);
  $email = get_customer_email($customerno);
  $anrede = "Sehr geehrte Damen und Herren";
  if ($customer['title'] == 'Herr')
    $anrede = "Sehr geehrter Herr {$customer['name']}";
  elseif ($customer['title'] == 'Frau')
    $anrede = "Sehr geehrte Frau {$customer['name']}";
  $msg = "{$anrede}.

Herzlich willkommen bei schokokeks.org!

Wir freuen uns, dass Sie sich für schokokeks.org entschieden haben.

Um Ihnen den Einstieg besonders angenehm zu gestalten, haben wir in 
unserem Wiki eine Seite eingerichtet, die Ihnen die ersten Schritte 
erläutern soll.
Rufen Sie dazu bitte die Adresse 
 https://wiki.schokokeks.org/Erste_Schritte
auf.

Auch die anderen Bereiche des Wikis stecken voller Tipps und 
Informationen. Schauen Sie sich um, es lohnt sich!

";
 /*
  * FIXME: Diese Mail muss noch überarbeitet werden!
  */

  send_mail($email, "Willkommen bei schokokeks.org", $msg);
}





?>

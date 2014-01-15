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

require_once('newpass.php');
require_once('session/checkuser.php');

function send_user_token($username) 
{
  $token = get_user_token($username);
  $email = emailaddress_for_user($username);

  $tokenurl = config('webinterface_url').'/init'.$token.'?agb=1';

  $msg = 'Sie haben für Ihren Zugang bei '.config('company_name').' ein neues Passwort angefordert.
Bitte besuchen Sie folgende Adresse um Ihr Passwort neu zu setzen:
  '.$tokenurl.'

Mit freundlichen Grüßen,
Ihre Admins von '.config('company_name');
  send_mail($email, "Passwortanforderung fuer schokokeks.org", $msg);
}

function send_customer_token($customerno)
{
  $customerno = (int) $customerno;
  $token = get_customer_token($customerno);
  $customer = get_customer_info($customerno);
  if ($customer['email'] == '')
    system_failure('Für Ihr Kundenkonto ist keine E-Mail-Adresse eingetragen. Diese Funktion steht Ihnen daher nicht zur Verfügung.');
  $anrede = "Sehr geehrte Damen und Herren";
  if ($customer['title'] == 'Herr')
    $anrede = "Sehr geehrter Herr {$customer['name']}";
  elseif ($customer['title'] == 'Frau')
    $anrede = "Sehr geehrte Frau {$customer['name']}";
  $msg = "{$anrede},

Sie haben auf unserem Web-Administrations-Interface ein neues
Passwort für Ihren Kunden-Zugang angefordert.
Diese automatische Nachricht dient der Überprüfung Ihrer Identität.

Um sich ein neues Passwort setzen zu können, rufen Sie bitte den
folgenden Link auf:
 ".config('webinterface_url')."/go/index/validate_token.php?customerno={$customer['customerno']}&token={$token}

Sollte Ihr E-Mail-Programm diesen Link nicht korrekt an den Browser
übertragen, rufen Sie bitte die Seite
 ".config('webinterface_url')."/go/index/validate_token.php
auf und geben Sie die folgenden Daten ein:
 Kundennummer: {$customer['customerno']}
 Token:        {$token}

Diese Prozedur müssen Sie bis spätestens 24 Stunden nach Erhalt
dieser Nachricht durchführen, sonst verliert das Token seine
Gültigkeit.
";
  send_mail($customer['email'], "Passwortanforderung fuer Webinterface", $msg);
}



function send_mail($address, $subject, $body)
{
  if (strstr($subject, "\n") !== false)
    die("Zeilenumbruch im subject!");
  $header = "From: ".config('company_name')." Web Administration <noreply@".config('masterdomain').">\r\nReply-To: ".config('adminmail')."\r\nContent-Type: text/plain; charset=\"utf-8\"\r\nContent-Transfer-Encoding: 8bit";
  mail($address, $subject, $body, $header);
}



?>

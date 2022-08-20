<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('newpass.php');
require_once('session/checkuser.php');


function send_customer_token($customerno)
{
    $customerno = (int) $customerno;
    $token = get_customer_token($customerno);
    $customer = get_customer_info($customerno);
    if ($customer['email'] == '') {
        system_failure('Für Ihr Kundenkonto ist keine E-Mail-Adresse eingetragen. Diese Funktion steht Ihnen daher nicht zur Verfügung.');
    }
    $anrede = "Sehr geehrte Damen und Herren";
    if ($customer['title'] == 'Herr') {
        $anrede = "Sehr geehrter Herr {$customer['name']}";
    } elseif ($customer['title'] == 'Frau') {
        $anrede = "Sehr geehrte Frau {$customer['name']}";
    }
    $msg = "{$anrede},

Sie haben auf unserem Web-Administrations-Interface ein neues
Passwort für Ihren Kunden-Zugang angefordert.
Diese automatische Nachricht dient der Überprüfung Ihrer Identität.

Um sich ein neues Passwort setzen zu können, rufen Sie bitte den
folgenden Link auf:
 https://config.schokokeks.org/go/index/validate_token?customerno={$customer['customerno']}&token={$token}

Sollte Ihr E-Mail-Programm diesen Link nicht korrekt an den Browser
übertragen, rufen Sie bitte die Seite
 https://config.schokokeks.org/go/index/validate_token
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
    if (strstr($subject, "\n") !== false) {
        die("Zeilenumbruch im subject!");
    }
    $header = "From: schokokeks.org Web Administration <noreply@schokokeks.org>\r\nReply-To: root@schokokeks.org\r\nContent-Type: text/plain; charset=\"utf-8\"\r\nContent-Transfer-Encoding: 8bit";
    mail($address, $subject, $body, $header);
}

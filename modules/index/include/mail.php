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
require_once('inc/base.php');

function send_user_token($username)
{
    $token = get_user_token($username);
    $email = emailaddress_for_user($username);

    $tokenurl = config('webinterface_url') . '/init' . $token . '?agb=1';

    $msg = 'Sie haben für Ihren Zugang bei ' . config('company_name') . ' ein neues Passwort angefordert.
Bitte besuchen Sie folgende Adresse um Ihr Passwort neu zu setzen:
  ' . $tokenurl . '

Mit freundlichen Grüßen,
Ihre Admins von ' . config('company_name');

    $msg .= "\n\nDiese Anforderung haben wir am " . date("r") . " von der IP-Adresse\n{$_SERVER['REMOTE_ADDR']} erhalten.\nSofern Sie dies nicht ausgelöst haben, benachrichtigen Sie bitte den Support\ndurch eine Antwort auf diese E-Mail.";

    send_mail($email, "Passwortanforderung für schokokeks.org", $msg);
}

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
 " . config('webinterface_url') . "/go/index/validate_token.php?customerno={$customer['customerno']}&token={$token}

Sollte Ihr E-Mail-Programm diesen Link nicht korrekt an den Browser
übertragen, rufen Sie bitte die Seite
 " . config('webinterface_url') . "/go/index/validate_token.php
auf und geben Sie die folgenden Daten ein:
 Kundennummer: {$customer['customerno']}
 Token:        {$token}

Diese Prozedur müssen Sie bis spätestens 24 Stunden nach Erhalt
dieser Nachricht durchführen, sonst verliert das Token seine
Gültigkeit.
";
    send_mail($customer['email'], "Passwortanforderung für Webinterface", $msg);
}

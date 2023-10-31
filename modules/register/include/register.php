<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('mail.php');

function customer_with_email($email)
{
    $email = db_escape_string($email);
    $result = db_query("SELECT id FROM kundendaten.kunden WHERE email='{$email}' OR email_rechnung='{$email}' OR email_extern='{$email}' LIMIT 1;");
    if ($result->rowCount() == 0) {
        return null;
    } else {
        return $result->fetch(PDO::FETCH_OBJ)->id;
    }
}



function create_customer($data)
{
    if (customer_with_email($data['email']) !== null) {
        logger(LOG_WARNING, 'modules/register/include/register', 'register', "Attempt to create customer with duplicate email »{$data['email']}«");
        return null;
    }

    logger(LOG_INFO, 'modules/register/include/register', 'register', "Creating new account: " . print_r($data, true));

    db_query("INSERT INTO kundendaten.kunden (firma, nachname, vorname, anrede, email, erstellungsdatum,status) VALUES (:firma, :nachname, :vorname, :anrede, :email, CURDATE(), 3)", $data);
    $customerno = db_insert_id();
    return $customerno;
}


function send_initial_customer_token($customerno)
{
    $customerno = (int) $customerno;
    $token = get_customer_token($customerno);
    $customer = get_customer_info($customerno);
    $anrede = "Sehr geehrte Damen und Herren";
    if ($customer['title'] == 'Herr') {
        $anrede = "Sehr geehrter Herr {$customer['name']}";
    } elseif ($customer['title'] == 'Frau') {
        $anrede = "Sehr geehrte Frau {$customer['name']}";
    }
    $msg = "{$anrede},

wir freuen uns, Sie bei schokokeks.org begrüßen zu dürfen.


Sie haben sich als Kunde von schokokeks.org Webhosting 
angemeldet. Diese E-Mail ist ein Zwischenschritt um die Gültigkeit 
Ihrer E-Mail-Adresse zu überprüfen.

Um ein Passwort für Ihren Kunden-Zugang festzulegen, rufen Sie 
bitte die folgende Adresse auf:
 https://config.schokokeks.org/go/index/validate_token.php?customerno={$customer['customerno']}&token={$token}

Sollte Ihr E-Mail-Programm diesen Link nicht korrekt an den Browser
übertragen, rufen Sie bitte die Seite
 https://config.schokokeks.org/go/index/validate_token.php
auf und geben Sie die folgenden Daten ein:
 Kundennummer: {$customer['customerno']}
 Code:         {$token}

Diese Prozedur müssen Sie bis spätestens 24 Stunden nach Erhalt
dieser Nachricht durchführen, sonst verliert der Code seine
Gültigkeit und der Zugang wird wieder gelöscht.

Sofern Sie keinen Account bei schokokeks.org angemeldet haben, 
können Sie diese Nachricht ignorieren.
";
    send_mail($customer['email'], "Willkommen bei schokokeks.org Webhosting", $msg);
}


function notify_admins_about_new_customer($customerno)
{
    $customerno = (int) $customerno;
    $customer = get_customer_info($customerno);
    $msg = "Folgender Kunde hat sich gerade über's Webinterface neu angemeldet:

Kundennummer: {$customerno}
Name: {$customer['name']}
E-mail: {$customer['email']}

Registriert von IP-Adresse {$_SERVER['REMOTE_ADDR']}.
";
    send_mail("root@schokokeks.org", "[Webinterface] Neuer Kunde", $msg);
}

function welcome_customer($customerno)
{
    $customerno = (int) $customerno;
    $customer = get_customer_info($customerno);
    $anrede = "Sehr geehrte Damen und Herren";
    if ($customer['title'] == 'Herr') {
        $anrede = "Sehr geehrter Herr {$customer['name']}";
    } elseif ($customer['title'] == 'Frau') {
        $anrede = "Sehr geehrte Frau {$customer['name']}";
    }
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

    send_mail($customer['email'], "Willkommen bei schokokeks.org", $msg);
}

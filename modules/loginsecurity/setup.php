<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('totp.php');
require_once('inc/base.php');
require_once('inc/security.php');
require_role(ROLE_SYSTEMUSER);

$username = $_SESSION['userinfo']['username'];

$section = 'loginsecurity_overview';
title("Zwei-Faktor-Anmeldung für Ihren Account");

warning('Nach Einrichtung der Zwei-Faktor-Anmeldung benötigen Sie für jeden Login einen Einmal-Code, den Sie mit einem Code-Generator - meist ein Smartphone mit einer entsprechenden App - erzeugen. Dieser Code wird aus einem gemeinsamen Geheimnis und der aktuellen Zeit jeweils neu berechnet.');
output('<p>Zur Einrichtung der Zwei-Faktor-Anmeldung für den Benutzer <strong>' . $username . '</strong>, scannen Sie mit Ihrer Code-Generator-App den unten stehenden QR-Code oder geben Sie das Geheimnis/Secret manuell in den Code-Generator ein.</p>');

output('<h3>QR-Code für Code-Generator-App</h3>');
output('<p>Der Zugang wird erst dann mit dem zweiten Faktor geschützt, wenn Sie unten einmalig einen korrekten Code eingegeben haben.</p>');

if (!isset($_SESSION['totp_secret'])) {
    $_SESSION['totp_secret'] = generate_systemuser_secret();
}

$qrcode_image = generate_systemuser_qrcode_image($_SESSION['totp_secret']);
output('<p><img src="data:image/png;base64,' . base64_encode($qrcode_image) . '" /></p>
<p>Secret-Code für manuelle initialisierung des Code-Generators: <span style="font-size: 120%;">' . $_SESSION['totp_secret'] . '</span></p>');


$passed = false;
$totp_id = null;
if (isset($_POST['password']) && isset($_POST['token'])) {
    // Prüfen, ob das Passwort und der Code stimmen
    if (!check_systemuser_password($_POST['password'])) {
        input_error('Das Passwort scheint falsch zu sein.');
        $passed = false;
    }
    if (check_systemuser_totp($_SESSION['userinfo']['uid'], $_POST['token'])) {
        // Passwort stimmt, Token stimmt
        // Config abspeichern
        $description = null;
        if (isset($_POST['description'])) {
            $description = $_POST['description'];
        }
        $totp_id = save_totp_config($description);
        $passed = true;
    } else {
        input_error('Der Code hat nicht gestimmt. Bitte prüfen Sie auch, ob die Egeräte-Uhrzeit stimmt.');
        $passed = false;
    }
}


if ($passed) {
    output('<p>Der obige Code wurde als zweiter Faktor eingerichtet!</p><p>Bitte notieren Sie sich den folgenden Restore-Code für den Fall, dass der Code-Generator nicht mehr verfügbar ist (Beschädigung am Handy, ...).</p><p>Restore-Code: <span style="font-size: 120%;">' . totp_restoretoken($totp_id) . '</span></p>');
} else {

    $form = '<p>Geben Sie zur Identifikation bitte Ihr Passwort und den aktuell vom Generator erzeugten Code ein.</p>
<p>Passwort: <input type="password" name="password" /></p>
<p>Bezeichnung für diesen Code-Generator: <input type="text" name="description" /></p>
<p>Aktueller TOTP-Code: <input type="text" name="token" /></p>';

    $form .= '<p><input type="submit" value="Einrichten" /></p>';

    output(html_form('totp_setup', 'setup', '', $form));
}

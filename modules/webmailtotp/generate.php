<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/base.php');
require_role([ROLE_SYSTEMUSER, ROLE_MAILACCOUNT, ROLE_VMAIL_ACCOUNT]);

require_once('totp.php');

$username = urldecode($_REQUEST['username']);

$oldpw = $_REQUEST['oldpw'];
$newpw = $_REQUEST['newpw'];

if (!validate_password($username, $oldpw)) {
    system_failure('Ihr bestehendes Mailbox-Passwort hat nicht gestimmt.');
}

store_webmail_password($username, $oldpw, $newpw);
$secret = generate_secret($username);

$section = 'webmailtotp_overview';
title("Zwei-Faktor-Anmeldung am Webmailer");

output('<p>Bitte geben Sie den folgenden Initialisierungs-Code in Ihre TOTP-Software ein oder scannen Sie den QR-Code mit Ihrem Mobiltelefon.</p>');

$qrcode_image = generate_qrcode_image($secret);

output('<h4>Ihr Initialisierungs-Code</h4><p style="font-size: 120%;">' . $secret . '</p><p><img src="data:image/png;base64,' . base64_encode($qrcode_image) . '" alt="QR-Code"></p>');

output('<h3>Testen Sie es...</h3><p>Nachdem Sie den Startwert in Ihren TOTP-Generator eingegeben haben bzw. den QRCode eingescannt haben, erhalten Sie umgehend einen Zugangscode. Geben Sie diesen hier ein um die Funktion zu testen:</p>');

$form = '<p>Ihr Webmail-Benutzername: <input type="text" name="username" value="' . filter_output_html($username) . '"></p>
<p>Ihr neues Webmail-Passwort: <input type="password" name="webmailpass"></p>
<p>Der aktuellste Einmal-Code: <input type="text" name="totp_code" autocomplete="one-time-code" inputmode="numeric"></p>
<p><input type="submit" value="Prüfen!"></p>';


output(html_form('webmailtotp_test', 'test', '', $form));

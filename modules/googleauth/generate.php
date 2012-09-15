<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2012 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/base.php');
require_role(ROLE_SYSTEMUSER);

require_once('googleauth.php');

$username = urldecode($_REQUEST['username']);

$oldpw = $_REQUEST['oldpw'];
$newpw = $_REQUEST['newpw'];

if (! validate_password($username, $oldpw)) {
  system_failure('Ihr bestehendes Mailbox-Passwort hat nicht gestimmt.');
}

store_webmail_password($username, $oldpw, $newpw);
$secret = generate_secret($username);

$section='googleauth_overview';
title("Sicherer Zugang zum Webmailer");

output('<p>Bitte geben Sie den folgenden Initialisierungs-Code in Ihre Google-Authenticator-Software ein oder Scannen Sie den QR-Code mit der Google-Authenticator-App Ihres Mobiltelefons.</p>');

$qrcode_image = generate_qrcode_image($secret);

output('<h4>Ihr Initialisierungs-Code</h4><p style="font-size: 120%;">'.$secret.'</p><p><img src="data:image/png;base64,'.base64_encode($qrcode_image).'" /></p>');

output('<h3>Testen Sie es...</h3><p>Nachdem Sie den Startwert in Ihren Google-Authenticator eingegeben haben bzw. den QRCode eingescannt haben, erhalten Sie umgehend einen Zugangscode. Geben Sie diesen hier ein um die Funktion zu testen:</p>');

$form = '<p>Ihr Webmail-Benutzername: <input type="text" name="username" value="'.filter_input_general($username).'" /></p>
<p>Ihr neues Webmail-Passwort: <input type="password" name="webmailpass" /></p>
<p>Der aktuellste Einmal-Code von Google-Authenticator: <input type="text" name="ga_code" /></p>
<p><input type="submit" value="Prüfen!" /></p>';


output(html_form('googleauth_test', 'test', '', $form));


?>

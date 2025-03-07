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
require_role([ROLE_MAILACCOUNT, ROLE_VMAIL_ACCOUNT]);

title("Zwei-Faktor-Anmeldung am Webmailer");

output('<p>Sie können bei ' . config('company_name') . ' den Zugang zum Webmailer mit einem Zwei-Faktor-Prozess mit abweichendem Passwort schützen.</p>
<p>Dieses System schützt Sie vor mitgelesenen Tastatureingaben in nicht vertrauenswürdiger Umgebung z.B. in einem Internet-Café.</p>
<p>Beim Zwei-Faktor-Prozess müssen Sie zum Login ein festes Webmail-Passwort und zusätzlich ein variabler Code, den beispielsweise Ihr Smartphone erzeugen kann, eingeben. Da sich dieser Code alle 30 Sekunden ändert, kann ein Angreifer sich nicht später mit einem abgehörten Passwort noch einmal anmelden. Zum Erzeugen des Einmal-Codes benötigen Sie ein Gerät, das <strong>TOTP-Einmalcodes nach RFC 6238</strong> erzeugt. Beispiele dafür sind <a href="https://code.google.com/p/google-authenticator/">Google-Authenticator</a> oder <a href="https://f-droid.org/en/packages/org.cry.otp/">mOTP</a>. Meist ist dies ein Smartphone mit einer entsprechenden App.</p>
<p><strong>Beachten Sie:</strong> Die Zwei-Faktor-Anmeldung funktioniert nur für Webmail und bei diesem Webinterface, beim Login via IMAP wird weiterhin das Passwort Ihres Postfachs benötigt. Damit dieses Passwort von einem Angreifer nicht mitgelesen werden kann, müssen Sie zur Zwei-Faktor-Anmeldung unbedingt ein separates Webmail-Passwort festlegen.</p>
');


require_once('modules/email/include/hasaccount.php');
require_once('modules/email/include/vmail.php');

$username = $_SESSION['mailaccount'];
$id = account_has_totp($username);


output('<h3>Zwei-Faktor-Anmeldung für ' . htmlspecialchars($username) . '</h3>
<div style="margin-left: 2em;">');
if ($id) {
    output(addnew('delete', 'Zwei-Faktor-Anmeldung für dieses Postfach abschalten', 'id=' . $id, 'style="background-image: url(' . $prefix . 'images/delete.png); color: red;"'));
} else {
    output(addnew('setup', 'Zwei-Faktor-Anmeldung für dieses Postfach aktivieren', 'username=' . urlencode($username)));
}
output('</div>');

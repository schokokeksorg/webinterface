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

require_once('inc/base.php');
require_once('inc/security.php');
require_role(ROLE_SYSTEMUSER);

$username = urldecode($_REQUEST['username']);

$section='webmailtotp_overview';
title("Zwei-Faktor-Anmeldung am Webmailer");

output('<p><strong>Hinweise:</strong></p><ul><li>Nach Einrichtung der Zwei-Faktor-Anmeldung funktioniert bei der Anmeldung über <a href="'.config('webmail_url').'">die zentrale Webmail-Login-Seite</a> nur noch dieses Passwort zusammen mit dem Einmal-Code, der mit dem TOTP-Generator erzeugt wird.</li>
<li>Ihr bestehendes IMAP-Passwort wird mit dem neuen Passwort verschlüsselt.</li><li>Über IMAP bzw. POP3 kann weiterhin nur mit dem bisherigen Passwort zugegriffen werden.</li><li>Wenn Sie ihr IMAP-Passwort ändern, wird diese Zwei-Faktor-Anmeldung automatisch abgeschaltet.</li></ul>');

$form = '<p>Geben Sie zunächst bitte das bestehende Passwort des Postfachs <strong>'.filter_input_general($username).'</strong> ein:</p>
<p>Postfach-Passwort: <input type="password" name="oldpw" /></p>';

$form .= '<p>Geben sie hier bitte das neue Passwort ein, mit dem sich der Benutzer <strong>'.filter_input_general($username).'</strong> zukünftig anmelden muss.</p>
<p>Neues Webmail-Passwort: <input type="password" name="newpw" /></p>';

$form .= '<p><input type="submit" value="Einrichten" /></p>';

output(html_form('webmailtotp_setup', 'generate', 'username='.urlencode($username), $form));

?>

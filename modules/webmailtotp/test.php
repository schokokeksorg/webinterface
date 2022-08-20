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
require_once('inc/icons.php');
require_role([ROLE_SYSTEMUSER, ROLE_MAILACCOUNT, ROLE_VMAIL_ACCOUNT]);

require_once('totp.php');

$section='webmailtotp_overview';
title('Test der Zwei-Faktor-Anmeldung');

if (isset($_REQUEST['username'])) {
    $username = $_REQUEST['username'];
    $webmailpw = $_REQUEST['webmailpass'];
    $ga_code = $_REQUEST['totp_code'];

    if (! strstr($username, '@')) {
        // Default-Domainname
        $username = $username.'@'.config('masterdomain');
    }

    $success = true;

    if (! check_webmail_password($username, $webmailpw)) {
        input_error('Das Webmail-Passwort hat nicht gestimmt.');
        $success = false;
    }

    if (check_locked($username)) {
        input_error('Aufgrund einiger Fehlversuche wurde dieses Konto übergangsweise deaktiviert. Bitte warten Sie ein paar Minuten.');
        $success = false;
    } elseif (! check_totp($username, $ga_code)) {
        input_error('Der TOTP-Code wurde nicht akzeptiert.');
        $success = false;
    }


    if ($success) {
        output('<p>'.icon_ok().' Der Test war erfolgreich!');
    } else {
        output('<p>'.icon_error().' Der Test war leider nicht erfolgreich.');
    }


    output('<h3>Weiterer Test</h3>');
} else {
    $username = '';
    output('<p>Geben Sie hier die Login-Daten ein um Ihren Zugang zu testen.</p>');
}

$form = '<p>Ihr Webmail-Benutzername: <input type="text" name="username" value="'.filter_output_html($username).'" /></p>
<p>Ihr neues Webmail-Passwort: <input type="password" name="webmailpass" /></p>
<p>Der aktuellste Einmal-Code: <input type="text" name="totp_code" /></p>
<p><input type="submit" value="Prüfen!" /></p>';


output(html_form('webmailtotp_test', 'test', '', $form));

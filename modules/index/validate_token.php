<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

title("Passwort setzen");

$show = 'token';

if (isset($_REQUEST['customerno']) and isset($_REQUEST['token'])) {
    $customerno = (int) $_REQUEST['customerno'];
    $token = $_REQUEST['token'];

    require_once('newpass.php');
    require_once('inc/security.php');
    if (validate_token($customerno, $token)) {
        $show = 'password';
        if (isset($_POST['password'])) {
            if ($_POST['password'] != $_POST['password2']) {
                input_error("Die beiden Passwort-Eingaben stimmen nicht überein.");
            } elseif ($_POST['password'] == '') {
                input_error("Es kann kein leeres Passwort gesetzt werden");
            } elseif (($result = strong_password($_POST['password'])) !== true) {
                input_error("Das Passwort ist zu einfach ({$result})!");
            } else {
                require_once('session/checkuser.php');
                require_once('inc/base.php');
                logger(LOG_INFO, "modules/index/validate_token", "pwrecovery", "customer »{$customerno}« set a new password");
                set_customer_password($customerno, $_POST['password']);
                success_msg('Das Passwort wurde gesetzt!');
                set_customer_verified($customerno);
                set_customer_lastlogin($customerno);
                invalidate_customer_token($customerno);
                $_SESSION['role'] = ROLE_CUSTOMER;
                $_SESSION['customerinfo'] = get_customer_info($customerno);
                title("Passwort gesetzt");
                output('<p>Ihr neues Passwort wurde gesetzt, Sie können jetzt ' . internal_link("index", "die Web-Oberfläche sofort benutzen") . '.</p>');
                $show = null;
            }
        }
    } else {
        input_error("Der eingegebene Code war nicht korrekt. Bitte benutzen Sie die Kopieren &amp; Einfügen-Operation!");
    }
}

if ($show == 'password') {
    output('<p>Bitte legen Sie jetzt Ihr neues Kunden-Passwort fest.</p>
  <form method="post">
  <p style="display: none"><input type="hidden" name="customerno" value="' . $customerno . '" />
  <input type="hidden" name="token" value="' . $token . '" /></p>
  <p><span class="login_label">Neues Passwort:</span> <input type="password" name="password" size="30" /></p>
  <p><span class="login_label">Bestätigung:</span> <input type="password" name="password2" size="30" /></p>
  <p><span class="login_label">&#160;</span> <input type="submit" value="Passwort setzen" /></p>
  </form>');
} elseif ($show == 'token') {
    output('<p>Bitte geben Sie Ihre Kundennummer und den per E-Mail zugeschickten Code ein. Alternativ können sie den Link aus der E-Mail direkt aufrufen.</p>
  <form method="post">
  <p><span class="login_label">Kundennummer:</span> <input type="text" name="customerno" size="30" /></p>
  <p><span class="login_label">Code:</span> <input type="text" name="token" size="30" /></p>
  <p><span class="login_label">&#160;</span> <input type="submit" value="Überprüfen" /></p>
  </form>');
}

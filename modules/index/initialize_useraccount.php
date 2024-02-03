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
require_once('inc/security.php');

title("Passwort setzen");
$show = 'token';

if (isset($_SESSION['role']) && $_SESSION['role'] != ROLE_ANONYMOUS) {
    @session_destroy();

    header('Location: ' . $_SERVER['PHP_SELF']);
    die();
}

if (isset($_REQUEST['token'])) {
    $token = $_REQUEST['token'];
    $uid = get_uid_for_token($token);

    if ($uid != null && validate_uid_token($uid, $token)) {
        $show = 'agb';
        if (isset($_REQUEST['agb']) && $_REQUEST['agb'] == '1') {
            $show = 'password';
        }
        if (isset($_POST['password'])) {
            if ($_POST['password'] != $_POST['password2']) {
                input_error("Die beiden Passwort-Eingaben stimmen nicht überein.");
            } elseif ($_POST['password'] == '') {
                input_error("Es kann kein leeres Passwort gesetzt werden");
            } elseif (preg_match('/["\'\\\\]/', $_POST['password']) === 1) {
                input_error("Das Passwort enthält problematische Zeichen. Bitte keine Anführungszeichen und kein Backslash benutzen.");
            } elseif (($result = strong_password($_POST['password'])) !== true) {
                input_error("Das Passwort ist zu einfach ({$result})!");
            } else {
                require_once('session/checkuser.php');
                require_once('inc/base.php');
                logger(LOG_INFO, "modules/index/initialize_useraccount", "initialize", "uid »{$uid}« set a new password");
                set_systemuser_password($uid, $_POST['password']);
                invalidate_systemuser_token($uid);
                $_SESSION['role'] = find_role($uid, '', true);
                ;
                setup_session($_SESSION['role'], $uid, 'initialize');
                success_msg('Das Passwort wurde gesetzt!');
                redirect('index');
            }
        }
    } else {
        input_error("Der eingegebene Code war nicht korrekt. Eventuell haben Sie die Adresse nicht vollständig übernommen oder die Gültigkeit des Sicherheitscodes ist abgelaufen.");
    }
}

if ($show == 'password') {
    $username = get_username_for_uid($uid);
    title("Neues Passwort setzen");
    output('<p>Bitte legen Sie jetzt Ihr neues Passwort fest.</p>' .
  html_form('initialize_useraccount', '', '', '<p style="display: none"><input type="hidden" name="uid" value="' . $uid . '">
  <input type="hidden" name="token" value="' . $token . '"><input type="hidden" name="agb" value="1"></p>
  <p><span class="login_label">Ihr Benutzername:</span> <strong>' . $username . '</strong></p>
  <p><span class="login_label">Neues Passwort:</span> <input type="password" name="password" size="30" autocomplete="new-password"></p>
  <p><span class="login_label">Bestätigung:</span> <input type="password" name="password2" size="30" autocomplete="new-password"></p>
  <p><span class="login_label">&#160;</span> <input type="submit" value="Passwort setzen"></p>
  '));
} elseif ($show == 'agb') {
    title("Bestätigung unserer AGB");
    output('<p>Die Nutzung unseres Angebots ist an unsere <a href="https://schokokeks.org/agb">Allgemeinen Geschäftsbedingungen</a> gebunden. Bitte lesen Sie diese Bedingungen und bestätigen Sie Ihr Einverständnis. Sollten Sie diese Bedingungen nicht akzeptieren, setzen Sie sich bitte mit uns in Verbindung.</p>' .
  html_form('initialize_useraccount_agb', '', '', '<p style="display: none"><input type="hidden" name="uid" value="' . $uid . '">
  <input type="hidden" name="token" value="' . $token . '"></p>
  <p><span class="login_label">&#160;</span><input type="checkbox" name="agb" value="1"> Ja, ich akzeptiere die AGB.<p>
  <p><span class="login_label">&#160;</span> <input type="submit" value="Weiter"></p>
  '));
} elseif ($show == 'token') {
    title("Neues Passwort setzen");
    output('<p>Bitte rufen Sie die Adresse aus Ihrer Begrüßungs-E-Mail auf um ein neues Passwort zu setzen.');
}

<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2018 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/debug.php');
require_once('inc/security.php');
require_role(array(ROLE_SYSTEMUSER, ROLE_CUSTOMER, ROLE_SUBUSER));

title("Passwort ändern");
$error = '';



if (isset($_POST['password1'])) {
    check_form_token('index_chpass');
    $result = null;
    if ($_SESSION['role'] & ROLE_SYSTEMUSER) {
        if ($_SESSION['role'] & ROLE_SUBUSER) {
            $result = find_role($_SESSION['subuser'], $_POST['old_password']);
        } else {
            $result = find_role($_SESSION['userinfo']['uid'], $_POST['old_password']);
        }
    } else {
        $result = find_role($_SESSION['customerinfo']['customerno'], $_POST['old_password']);
    }

    if ($result == null) {
        input_error('Das bisherige Passwort ist nicht korrekt!');
    } elseif ($_POST['password2'] != $_POST['password1']) {
        input_error('Die Bestätigung ist nicht identisch mit dem neuen Passwort!');
    } elseif ($_POST['password2'] == '') {
        input_error('Sie müssen das neue Passwort zweimal eingeben!');
    } elseif ($_POST['old_password'] == '') {
        input_error('Altes Passwort nicht angegeben!');
    } elseif (($check = strong_password($_POST['password1'])) !== true) {
        input_error("Das Passwort ist zu einfach ({$check})!");
    } else {
        if ($result & ROLE_SYSTEMUSER) {
            set_systemuser_password($_SESSION['userinfo']['uid'], $_POST['password1']);
        } elseif ($result & ROLE_SUBUSER) {
            set_subuser_password($_SESSION['subuser'], $_POST['password1']);
        } elseif ($result & ROLE_CUSTOMER) {
            set_customer_password($_SESSION['customerinfo']['customerno'], $_POST['password1']);
        } else {
            system_failure("WTF?! (\$result={$result})");
        }

        if (! $debugmode) {
            header('Location: index');
        } else {
            output('');
        }
    }
}



if ($_SESSION['role'] & ROLE_SYSTEMUSER && ! ($_SESSION['role'] & ROLE_SUBUSER)) {
    warning('Beachten Sie: Wenn Sie hier Ihr Passwort ändern, betrifft dies auch Ihr Anmelde-Passwort am Server (SSH).');
}

output('<p>Hier können Sie Ihr Passwort ändern.</p>
'.html_form('index_chpass', 'chpass', '', '<table>
  <tr>
    <td>bisheriges Passwort:</td>  <td><input type="password" name="old_password" value="" /></td>
  </tr>
  <tr>
    <td>neues Passwort:</td>       <td><input type="password" name="password1" value="" /></td>
  </tr>
  <tr>
    <td>Bestätigung:<br /><span style="font-size: 80%;">(nochmal neues Passwort)</span></td>
                                   <td><input type="password" name="password2" value="" /></td>
  </tr>
</table>
<p><input type="submit" value="Speichern" /></p>
'));

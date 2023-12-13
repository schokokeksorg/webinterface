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

require_once('useraccounts.php');

require_role(ROLE_CUSTOMER);


title("Passwort neu setzen");
$section = "systemuser_account";

$account = get_account_details($_GET['uid']);


headline("Rücksetzen des Passworts für Benutzer »{$account['username']}«");

if (customer_useraccount($account['uid'])) {
    system_failure('Zum Ändern des Passwortes für den Hauptbenutzer verwenden Sie bitte die entsprechende Funktion im Hauptmenü!');
}

output(html_form('systemuser_pwchange', 'save', 'action=pwchange&uid=' . $account['uid'], '

<h5>Neues Passwort</h5>
<div style="margin-left: 2em;"> 
  <p>Geben Sie bitte Ihr neues Passwort zweimal ein. Bitte verzichten Sie auf Anführungszeichen!</p>
  <p><label for="newpass1">Neues Passwort für »<strong>' . $account['username'] . '</strong>«:</label> <input type="password" name="newpass1" id="newpass1" autocomplete="new-password" /></p>
  <p><label for="newpass2">Wiederholung des Passworts:</label> <input type="password" name="newpass2" id="newpass2" autocomplete="new-password" /></p>
</div>

<p>
<input type="submit" name="submit" value="Speichern" />
</p>
'));

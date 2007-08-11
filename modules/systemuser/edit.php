<?php

require_once('session/start.php');

require_once('useraccounts.php');

require_role(ROLE_CUSTOMER);


$title = "System-Benutzeraccounts";
$section = "systemuser_accounts";

$account = get_account_details($_GET['uid']);

output("<h3>Bearbeiten von Benutzer »{$account['username']}«</h3>");

if (customer_useraccount($account['uid']))
  system_failure('Aus Sicherheitsgründen können Sie diesen Account nicht ändern!');


output(html_form('systemuser_edit', 'save.php', 'action=edit', '
<table>
<tr><td>Benutzername:</td><td><strong>'.$account['username'].'</strong></td></tr>
<tr><td>richtiger Name:<br /><span style="font-size:85%;">(wenn nicht »'.$_SESSION['customerinfo']['name'].'«)</span></td><td><input type="text" name="fullname" value="'.$account['name'].'" /></td></tr>
<tr><td>Passwort:</td><td><input type="password" name="newpass" value="" /><br /><span style="font-size:85%;">(Bitte leer lassen um das Passwort nicht zu ändern!)</span></td></tr>
<tr><td>Wiederholung:</td><td><input type="password" name="newpass2" value="" /></td></tr>
</table>
<p>
<input type="hidden" name="uid" value="'.$account['uid'].'" />
<input type="submit" name="submit" value="Speichern" />
</p>
'));



?>

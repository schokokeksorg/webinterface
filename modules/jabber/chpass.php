<?php

require_once('session/start.php');

require_once('jabberaccounts.php');

require_role(ROLE_CUSTOMER);

$section = 'jabber_accounts';
$title = "Neues Jabber-Konto erstellen";

$account = get_jabberaccount_details($_GET['account']);
$account_string = $account['local'].'@'.$account['domain'];

output("<h3>Passwort für Jabber-Account ändern</h3>");

output(html_form('jabber_chpass', 'save', 'action=chpass', '
<table>
<tr><td>Account-Name:</td><td>'.$account_string.'</td></tr>
<tr><td>Passwort:</td><td><input type="password" name="newpass" value="" /></td></tr>
<tr><td>Wiederholung:</td><td><input type="password" name="newpass2" value="" /></td></tr>
</table>
<br />
<input type="hidden" name="accountid" value="'.$account['id'].'" />
<input type="submit" name="submit" value="Speichern" />
'));


?>

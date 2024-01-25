<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('session/start.php');

require_once('jabberaccounts.php');

require_role(ROLE_CUSTOMER);

$section = 'jabber_accounts';
title("Passwort für Jabber-Account ändern");

$account = get_jabberaccount_details($_GET['account']);
$account_string = $account['local'] . '@' . $account['domain'];

output(html_form('jabber_chpass', 'save', 'action=chpass', '
<table>
<tr><td>Account-Name:</td><td>' . $account_string . '</td></tr>
<tr><td>Passwort:</td><td><input type="password" name="newpass" value="" autocomplete="new-password"></td></tr>
<tr><td>Wiederholung:</td><td><input type="password" name="newpass2" value="" autocomplete="new-password"></td></tr>
</table>
<br>
<input type="hidden" name="accountid" value="' . $account['id'] . '">
<input type="submit" name="submit" value="Speichern">
'));

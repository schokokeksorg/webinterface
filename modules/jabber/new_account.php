<?php

require_once('session/start.php');

require_once('class/domain.php');
require_once('jabberaccounts.php');

require_role(ROLE_CUSTOMER);

$section = 'jabber_accounts';
title("Neues Jabber-Konto erstellen");

$jabberdomains = get_jabberable_domains();

DEBUG($jabberdomains);

$options = '';
foreach ($jabberdomains as $dom)
{
  $options .= '<option value="'.$dom->id.'">'.$dom->fqdn.'</option>'."\n";
}


output('<p>Erstellen Sie hier ein neues Jabber-Konto. Ihre Änderungen werden nach ca. 10 Minuten automatisch in das System übertragen. Accounts funktionieren also nicht unmittelbar nach dem Anlegen.</p>

'.html_form('jabber_new_account', 'save', 'action=new', '
<table>
<tr><td>Account-Name:</td><td><input type="text" name="local" value="" />&#160;@&#160;<select name="domain" size="1">
'.$options.'
</select></td></tr>
<tr><td>Passwort:</td><td><input type="password" name="password" value="" /></td></tr>
</table>
<br />
<input type="submit" name="submit" value="Anlegen" />
'));


?>

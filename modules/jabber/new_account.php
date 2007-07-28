<?php

require_once('session/start.php');

require_once('class/domain.php');
require_once('jabberaccounts.php');

require_role(ROLE_CUSTOMER);

$title = "Neues Jabber-Konto erstellen";

$jabberdomains = get_jabberable_domains();

$options = '';
foreach ($jabberdomains as $dom)
{
  $options .= '<option value="'.$dom->id.'">'.$dom->fqdn.'</option>'."\n";
}


output("<h3>Neuen Jabber-Account erstellen</h3>");

output('<p>Erstellen Sie hier ein neues Jabber-Konto.</p>

'.html_form('jabber_new_account', 'save.php', 'action=new', '
<table>
<tr><td>Account-Name:</td><td><input type="text" name="local" value="" />&nbsp;@&nbsp;<select name="domain" size="1">
'.$options.'
</select></td></tr>
<tr><td>Passwort:</td><td><input type="password" name="password" value="" /></td></tr>
</table>
<br />
<input type="submit" name="submit" value="Anlegen" />
'));


?>

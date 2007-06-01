<?php

require_once('session/start.php');

require_once('domains.php');
require_once('jabberaccounts.php');

require_role(ROLE_CUSTOMER);

DEBUG("GET: ".htmlentities(print_r($_GET, true))." / POST: ".htmlentities(print_r($_POST, true)));

$jabberaccounts = get_jabber_accounts();

output("<h3>Jabber-Accounts</h3>


<table>
");

foreach ($jabberaccounts as $acc)
{
  $domain = get_domain_name($acc['domain']);
  output("<tr><td>{$acc['local']}@$domain</td><td>".internal_link('chpass.php', 'Passwort ändern', 'account='.$acc['id'])."&nbsp;&nbsp;&nbsp;".internal_link('save.php', 'Löschen', 'action=delete&account='.$acc['id']).'</td></tr>');
}

output('</table>

<p><a href="new_account.php">Neues Jabber-Konto anlegen</a></p>');

?>

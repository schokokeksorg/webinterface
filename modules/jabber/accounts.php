<?php

require_once('session/start.php');

require_once('class/domain.php');
require_once('jabberaccounts.php');

require_once('inc/security.php');

require_role(ROLE_CUSTOMER);

$jabberaccounts = get_jabber_accounts();

output("<h3>Jabber-Accounts</h3>


<table>
");

foreach ($jabberaccounts as $acc)
{
  $local = filter_input_general($acc['local']);
  $domain = new Domain( (int) $acc['domain']  );
  if ($domain->id == NULL)
  {
    $domain = new Domain();
    $domain->fqdn='schokokeks.org';
  }
  output("<tr><td>{$local}@{$domain->fqdn}</td><td>".internal_link('chpass.php', 'Passwort ändern', 'account='.$acc['id'])."&#160;&#160;&#160;".internal_link('save.php', 'Löschen', 'action=delete&account='.$acc['id']).'</td></tr>');
}

output('</table>

<p><a href="new_account.php">Neues Jabber-Konto anlegen</a></p>');

?>

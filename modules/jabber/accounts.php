<?php

require_once('session/start.php');

require_once('class/domain.php');
require_once('jabberaccounts.php');

require_once('inc/security.php');
require_once('inc/icons.php');

require_role(ROLE_CUSTOMER);

$jabberaccounts = get_jabber_accounts();

title("Jabber-Accounts");

output("<table>");

foreach ($jabberaccounts as $acc)
{
  $local = filter_input_general($acc['local']);
  $domain = new Domain( (int) $acc['domain']  );
  if ($domain->id == NULL)
  {
    $domain = new Domain();
    $domain->fqdn = config('masterdomain');
  }
  output("<tr><td>{$local}@{$domain->fqdn}</td><td>".internal_link('chpass', icon_pwchange('Passwort ändern'), 'account='.$acc['id'])."&#160;&#160;&#160;".internal_link('save', icon_delete("»{$local}@{$domain->fqdn}« löschen"), 'action=delete&account='.$acc['id']).'</td></tr>');
}

output('</table>');

addnew("new_account", "Neues Jabber-Konto anlegen");
addnew("new_domain", "Eigene Domain für Jabber freischalten");

?>

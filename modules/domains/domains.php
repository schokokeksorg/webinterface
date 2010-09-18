<?php

require_once('inc/debug.php');

require_once('session/start.php');

require_once('class/domain.php');
require_once('domains.php');

require_role(array(ROLE_SYSTEMUSER, ROLE_CUSTOMER));

if ($_SESSION['role'] & ROLE_CUSTOMER)
  $user_domains = get_domain_list($_SESSION['customerinfo']['customerno']);
else
  $user_domains = get_domain_list($_SESSION['userinfo']['customerno'], $_SESSION['userinfo']['uid']);

title("Domains");

output('<p>In Ihrem Account werden die folgenden Domains verwaltet:</p>
<table>
<tr><th>Domainname</th><th>Status</th><th>Funktionen</th></tr>
');
foreach ($user_domains as $domain)
{
  $regdate = $domain->reg_date;
  if ($domain->provider != 'terions')
    $regdate = '<em>Extern registriert</em>';
  elseif ($domain->reg_date == NULL)
    $regdate = '<em>Umzug bevorstehend</em>';
  else
    $regdate = 'Registriert seit '.$regdate;

  if ($domain->cancel_date) {
    $regdate .= '<br />Gekündigt zum '.$domain->cancel_date;
  }

  $features = array();
  if ($domain->dns == 1) {
    if (dns_in_use($domain->id))
      $features[] = 'DNS';
    //if ($domain->autodns == 1)
    //  $features[] = 'AutoDNS';
  }
  $mailman = mailman_subdomains($domain->id);
  if (mail_in_use($domain->id))
    $features[] = 'Mail';
  if ($mailman)
    $features[] = 'Mailinglisten';
  if (web_in_use($domain->id))
    $features[] = 'Web';
  if ($domain->jabber == 1)
    $features[] = 'Jabber';

  $features = implode(', ', $features);
  if (! $features)
    $features = '<em>unbenutzt</em>';
  output("  <tr><td>{$domain->fqdn}</td><td>{$regdate}</td><td>{$features}</td></tr>\n");
}
output('</table>');
output("<br />");



?>

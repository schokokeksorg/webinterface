<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2018 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

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
  $mailserver_lock = '';
  if ($domain->mailserver_lock == 1) {
      $mailserver_lock = '<br><strong>Mail-Verarbeitung eingeschränkt!</strong>'.footnote('Diese Domain ist extern registriert und wurde noch nicht bestätigt. Momentan ist daher der Mail-Empfang auf dieser Domain nicht möglich.');
  }
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
  $punycode = $domain->punycode;
  if ($domain->is_idn) {
    $punycode = "<br/>($punycode)";
  } else {
    $punycode = '';
  }
  $domainname = "{$domain->fqdn}{$punycode}";
  if ((!$domain->cancel_date || $domain->cancel_date > date('Y-m-d')) && have_module('contacts') && $_SESSION['role'] & ROLE_CUSTOMER && update_possible($domain->id)) {
      $domainname = internal_link('update', $domainname, 'id='.$domain->id);
  } elseif ($_SESSION['role'] & ROLE_CUSTOMER && $domain->mailserver_lock == 1) {
      $domainname = internal_link('verify', $domainname, 'id='.$domain->id);
  }
  output("  <tr><td>{$domainname}</td><td>{$regdate}</td><td>{$features}{$mailserver_lock}</td></tr>\n");
}
output('</table>');
output("<br />");



?>

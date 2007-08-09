<?php

require_once('inc/debug.php');

require_once('session/start.php');

require_once('class/domain.php');

require_role(array(ROLE_SYSTEMUSER, ROLE_CUSTOMER));

if ($_SESSION['role'] & ROLE_CUSTOMER)
  $user_domains = get_domain_list($_SESSION['customerinfo']['customerno']);
else
  $user_domains = get_domain_list($_SESSION['userinfo']['customerno'], $_SESSION['userinfo']['uid']);

$title = "Domainüberblick";

output('<h3>Domains</h3>
<p>In Ihrem Account werden die folgenden Domains verwaltet:</p>
<table>
<tr><th>Domainname</th><th>Reg-Datum</th><th>Kündigungsdatum</th><th>&nbsp;</th></tr>
');
foreach ($user_domains as $domain)
{
  output("  <tr><td>{$domain->fqdn}</td><td>{$domain->reg_date}</td><td>{$domain->cancel_date}</td><td><a href=\"http://www.{$domain->fqdn}\">WWW-Seite aufrufen</a></td></tr>\n");
}
output('</table>');
output("<br />");



?>

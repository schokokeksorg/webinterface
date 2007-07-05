<?php

require_once('inc/debug.php');

require_once('session/start.php');

require_once('class/domain.php');

require_role(array(ROLE_SYSTEMUSER, ROLE_CUSTOMER));

switch ($_SESSION['role'])
{
  case ROLE_SYSTEMUSER:
    $user_domains = get_domain_list($_SESSION['userinfo']['customerno'], $_SESSION['userinfo']['uid']);
    $info = 'userinfo';
    break;
  case ROLE_CUSTOMER:
    $user_domains = get_domain_list($_SESSION['customerinfo']['customerno']);
    break;
  default:
    $info = NULL;
    break;
}

$title = "Domainüberblick";

output('<h3>Domains</h3>
<p>In Ihrem Account werden die folgenden Domains verwaltet:</p>
<table>
<tr><th>Domainname</th><th>Reg-Datum</th><th>Kündigungsdatum</th></tr>
');
foreach ($user_domains as $domain)
{
  output("  <tr><td><a href=\"http://www.{$domain->fqdn}\">{$domain->fqdn}</a></td><td>{$domain->reg_date}</td><td>{$domain->cancel_date}</td></tr>\n");
}
output('</table>');
output("<br />");



?>

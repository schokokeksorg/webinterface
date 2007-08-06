<?php

require_once('inc/debug.php');
require_once('inc/security.php');

require_once('vhosts.php');

$title = "VHost bearbeiten";

require_role(ROLE_SYSTEMUSER);

$vhost = get_vhost_details($_GET['vhost']);

DEBUG($vhost);
output("<h3>VHost bearbeiten</h3>");

$s = (strstr($vhost['options'], 'aliaswww') ? ' checked="checked" ' : '');
$form = "
  <table>
    <tr><th>Einstellung</th><th>aktueller Wert</th><th>System-Standard</th></tr>
    <tr><td>Name</td>
    <td><div id=\"wwwprefix\" style=\"font-weight: bold; display: inline; color: #000;\">www.</div><input type=\"text\" name=\"hostname\" size=\"10\" value=\"{$vhost['hostname']}\" />
".domainselect($vhost['domain_id']);
$form .= "<br /><input type=\"checkbox\" name=\"options[]\" value=\"aliaswww\" onclick=\"document.getElementById('wwwprefix').firstChild='';\" {$s}/> Ohne <strong>www</strong> davor.</td><td><em>keiner</em></td></tr>";

$form .= '</table>';
output(html_form('vhosts_edit_vhost', 'save.php', '', $form));


?>

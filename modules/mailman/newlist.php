<?php
require_once('mailman.php');
require_role(ROLE_SYSTEMUSER);

$title = "Neue Mailingliste erstellen";
$section = 'mailman_lists';
$domains = get_mailman_domains();

$maildomains = array('0' => 'lists.schokokeks.org');
foreach ($domains AS $domain)
{
  $maildomains[$domain['id']] = $domain['fqdn'];
}

output("<h3>Neue Mailingliste erstellen</h3>");

output('<p>Tragen Sie hier die benötigten Daten zur Erstellung einer neuen Mailingliste ein. Die Liste wird <strong>mit etwas Zeitverzögerung</strong> angelegt, Sie erhalten dann eine E-Mail an die unten angegebene Adresse des Listen-Verwalters</p>

'.html_form('mailman_newlist', 'save', 'action=new', '
<table>
<tr><td>Listenname:</td><td><input type="text" name="listname" value="" />&#160;@&#160;'.html_select('maildomain', $maildomains).'</td></tr>
<tr><td>E-Mail-Adresse des Listen-Verwalters:</td><td><input type="text" name="admin" value="'.$_SESSION['userinfo']['username'].'@'.$config['masterdomain'].'" /></td></tr>
</table>
<br />
<input type="submit" name="submit" value="Anlegen" />
'));


?>

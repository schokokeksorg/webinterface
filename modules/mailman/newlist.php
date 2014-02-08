<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2014 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('mailman.php');
require_role(ROLE_SYSTEMUSER);

title("Neue Mailingliste erstellen");
$section = 'mailman_lists';
$domains = get_mailman_domains();

$maildomains = array('0' => config('mailman_host'));
foreach ($domains AS $domain)
{
  $maildomains[$domain['id']] = $domain['fqdn'];
}

output('<p>Tragen Sie hier die benötigten Daten zur Erstellung einer neuen Mailingliste ein. Die Liste wird <strong>mit etwas Zeitverzögerung</strong> angelegt, Sie erhalten dann eine E-Mail an die unten angegebene Adresse des Listen-Verwalters</p>

'.html_form('mailman_newlist', 'save', 'action=new', '
<table>
<tr><td>Listenname:</td><td><input type="text" name="listname" value="" />&#160;@&#160;'.html_select('maildomain', $maildomains).'</td></tr>
<tr><td>E-Mail-Adresse des Listen-Verwalters:</td><td><input type="text" name="admin" value="'.$_SESSION['userinfo']['username'].'@'.config('masterdomain').'" /></td></tr>
</table>
<br />
<input type="submit" name="submit" value="Anlegen" />
').'

<p><strong>Hinweis zu Domains:</strong> Aufgrund der Architektur von Mailman ist es momentan notwendig, bestimmte Domains oder Subdomains vollständig auf Mailman zu konfigurieren. Unter diesen Domains oder Subdomains kann keine anderweitige E-Mail-Adresse benutzt werden. Sofern Sie eine Ihrer eigenen Domains oder eine Subdomain unter einer Ihrer Domains für Mailinglisten benutzen möchten, müssen Sie diese Domain oder Subdomain vorher von einem Administrator anlegen lassen. Sie können danach in dieser Auswahlliste Ihre eigene Domain wählen.</p>'
);


?>

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

require_once('session/start.php');

require_once('inc/security.php');
require_once('inc/icons.php');

require_once('adddomain.php');

require_role(ROLE_CUSTOMER);

title("Domain hinzufügen");
$section = 'adddomain_search';

output('<p>Mit dieser Funktion können Sie eine neue Domain in unsere Datenbank eintragen.</p>
<p><strong>WICHTIG:</strong> Diese Funktion löst nicht die Registrierung der Domain aus und diese Funktion erteilt uns auch nicht den Auftrag, eine Domain zu registrieren. Die Registrierung müssen Sie selbst durchführen oder separat bei uns beauftragen.</p>');

$data = get_domain_offer($_REQUEST['domain']);

if (! $data) {
  // Die Include-Datei setzt eine passende Warning-Nachricht
  redirect('search');
}

$users = list_useraccounts();
$userselect = array();
foreach ($users as $u) {
  $userselect[$u['uid']] = $u['username'].' / '.$u['name'];
}

$form = '<table>
<tr><td>Domainname:</td><td><strong>'.$data['domainname'].'</strong></td></tr>
<tr><td>Jahresgebühr:</td><td style="text-align: right;">'.$data['gebuehr'].' €</td></tr>
<tr><td>Setup-Gebühr (einmalig):</td><td style="text-align: right;">'.$data['setup'].' €</td></tr>
<tr><td>Benutzeraccount:</td><td>'.html_select('uid', $userselect).'</td></tr>
</table>

<input type="submit" value="Domain eintragen" />';

output(html_form('adddomain_add', 'save', 'domain='.$data['domainname'], $form));

output("<p><strong>Hinweis:</strong> Die hier angegebenen Beträge wurden automatisch aus unserer Preisliste ermittelt und werden zur Abrechnung verwendet. Sollten diese nicht der Vereinbarung entsprechen, teilen Sie uns dies bitte umgehend mit, damit wir dies korrigieren können.</p>");

?>

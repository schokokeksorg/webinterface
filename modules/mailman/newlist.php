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

require_once('mailman.php');
require_role(ROLE_SYSTEMUSER);

title("Neue Mailingliste erstellen");
$section = 'mailman_lists';
$domains = get_mailman_domains();

$maildomains = ['0' => config('mailman_host')];
foreach ($domains as $domain) {
    $maildomains[$domain['id']] = $domain['fqdn'];
}

$newdomains = get_possible_mailmandomains();
if ($newdomains) {
    $maildomains[null] = '--------------------------';
    foreach ($newdomains as $domain) {
        $maildomains['d'.$domain['id']] = 'lists.'.$domain['fqdn'];
    }
}
output(
    '<p>Erstellen Sie hier eine neue Mailingliste auf unserem zentralen Mailinglisten-Manager (Mailman). Die Liste wird <strong>mit etwas Zeitverzögerung</strong> angelegt, Sie erhalten dann eine E-Mail an die unten angegebene Adresse des Listen-Verwalters.</p>
    <p><strong>Hinweis zum Listen-Verwalter:</strong> Der Listen-Verwalter bzw. Moderator erhält später im Betrieb auch die Nachrichten, die Mailman nicht zur Liste sendet mit der Bitte um Moderation/Freigabe. Bitte geben Sie hier eine E-Mail-Adresse an, die über keinen besonders aggressiven Spamfilter verfügt und auf der keine Autoresponder aktiviert werden.</p>

'.html_form('mailman_newlist', 'save', 'action=new', '
<table>
<tr><td>Listenname:</td><td><input type="text" name="listname" value="" />&#160;@&#160;'.html_select('maildomain', $maildomains, '0').'</td></tr>
<tr><td>E-Mail-Adresse des Listen-Verwalters:</td><td><input type="text" name="admin" value="'.$_SESSION['userinfo']['username'].'@'.config('masterdomain').'" /></td></tr>
</table>
<br />
<input type="submit" name="submit" value="Anlegen" />
').'

<h4>Hinweis zu Domains:</h4>
<p>Die Angabe der Listen-Domain ist bei Mailman eher kosmetischer Natur. Auch wenn Sie eine eigene Domain benutzen, muss der Listennamen dennoch eindeutig auf dem gesamten Server sein.</p>
<p>Aufgrund der Architektur von Mailman ist es zudem notwendig, für einen Hostname jeweils die Mail-Zustellung fest auf Mailman zu konfigurieren. Unter diesen Subdomains kann keine anderweitige E-Mail-Adresse benutzt werden. Sofern Sie erstmalig eine Ihrer eigenen Domains für eine Mailingliste wählen (im Auswahlfeld unter der Linie) wird eine entsprechende Konfiguration erstellt. Die Liste ist in dem Fall erst nach einigen Minuten (bis zu maximal einer Stunde) für eingehende E-Mails erreichbar.</p>'
);

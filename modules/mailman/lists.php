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

require_once('inc/jquery.php');
require_once('inc/icons.php');
require_once('mailman.php');

require_role(ROLE_SYSTEMUSER);

title('Mailinglisten');

output('<p>Mit <a href="https://www.gnu.org/software/mailman/index.html">Mailman</a> bieten wir Ihnen eine umfangreiche Lösung für E-Mail-Verteilerlisten an.</p>
<p>Auf dieser Seite können Sie Ihre Mailinglisten verwalten.</p>
');

$filter = "";
if (isset($_REQUEST['filter']) && $_REQUEST['filter'] != '') {
    $filter = $_REQUEST['filter'];
}
$lists = get_lists($filter);


// Filter-Funktion
if (count($lists) > 10 || $filter) {
    javascript();
    $form = '<p><label for="filter">Filter für die Anzeige:</label> <input type="text" name="filter" id="filter" value="'.filter_output_html($filter).'"><button type="button" id="clear" title="Filter leeren">&times;</button><input type="submit" value="Filtern!"></p>';
    output(html_form('mailman_filter', 'lists', '', $form));
}


if (! empty($lists)) {
    addnew('newlist', 'Neue Mailingliste anlegen');
    output('<div id="mailman_lists_container">');
    foreach ($lists as $list) {
        $size = $list['archivesize'];
        $sizestr = $size.' Bytes';
        if (! $size) {
            $sizestr = '<em>Kein Archiv</em>';
        } else {
            $sizestr = sprintf('%.2f', $size/(1024*1024)).' MB';
        }


        $class = 'regular';
        $status = 'In Betrieb (erstellt am '.strftime('%d.%m.%Y', strtotime($list['created'])).')';
        if ($list['status'] == 'delete') {
            $class = 'deleted';
            $status = 'Wird gelöscht';
        } elseif ($list['status'] == 'pending') {
            $class = 'new';
            $status = 'Wird angelegt';
        } elseif ($list['status'] == 'newpw') {
            $class = 'edited';
            $status = 'Neues Passwort angefordert';
        } elseif ($list['status'] == 'failure') {
            $class = 'error';
            $status = 'Fehler bei der Erstellung';
        }

        $admin = str_replace(',', ', ', $list['admin']);

        $lastactivity = $list['lastactivity'];
        if (! $lastactivity || $lastactivity < '2000') {
            $lastactivity = '<em>nie</em>';
        }

        output("<div class=\"mailman_list $class\"><p class=\"listname\"><span class=\"listname\">{$list['listname']}</span>@{$list['fqdn']}</p>
        <p class=\"listadmin\">Verwalter: {$admin}</p><p class=\"status\">Status: {$status}<br/>Anzahl Mitglieder: {$list['subscribers']}<br/>Letzte Nutzung: {$lastactivity}</p><p class=\"archivesize\">Archivgröße: {$sizestr}</p>");
        if ($list['status'] == 'running') {
            output("<p class=\"operations\">".internal_link('save', other_icon("lock.png", "Neues Passwort anfordern").' Neues Passwort anfordern', "action=newpw&id={$list['id']}")."<br>".internal_link('save', icon_delete("Mailingliste löschen").' Liste löschen', "action=delete&id={$list['id']}")."<br><a href=\"https://".config('mailman_host')."/mailman/admin.cgi/{$list['listname']}\">".other_icon("database_go.png", "Listen-Verwaltung aufrufen")." Verwaltung aufrufen</a></p></div>\n");
        } else {
            output("</div>\n");
        }
    }
    output("</div>");
} else {
    // keine Listen
    output('<p><em>Sie betreiben bisher keine Mailinglisten.</em></p>');
}

addnew('newlist', 'Neue Mailingliste anlegen');
output("
<p><strong>Hinweise:</strong><br />
<sup>1</sup>) Sie können später im Webinterface von Mailman einen abweichenden oder auch mehrere Verwalter eintragen. Die Information auf dieser Seite wird zyklisch synchronisiert.<br />
<sup>2</sup>) Die Größe der Archive wird in regelmäßigen Abständen eingelesen. Der hier angezeigte Wert ist möglicherweise nicht mehr aktuell.</p>\n");

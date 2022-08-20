<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/jquery.php');
require_once('inc/icons.php');
require_once('mailman.php');

require_role(ROLE_SYSTEMUSER);

title('Mailinglisten');

output('<div class="warning">
    <p><strong>Bitte beachten Sie: Der Mailinglisten-Dienst wird zum Jahresende 2022 eingestellt. Das Anlegen neuer Listen ist nicht mehr möglich.<br>
    Wenden Sie sich bitte frühzeitig an den Support, wenn Sie Unterstützung beim Umzug zu einem anderen Dienstleister benötigen.</strong></p>
    </div>');

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
    #addnew('newlist', 'Neue Mailingliste anlegen');
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
        } elseif ($list['status'] == 'deleted') {
            # liste ist schon gelöscht
            continue;
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
            if ($list['backend'] == 'mailman' || $list['backend'] === null) {
                output("<p class=\"operations\">".
                    internal_link('save', other_icon("lock.png", "Neues Passwort anfordern").' Neues Passwort anfordern', "action=newpw&id={$list['id']}")."<br>".
                    internal_link('save', icon_delete("Mailingliste löschen").' Liste löschen', "action=delete&id={$list['id']}")."<br>".
                    "<a href=\"https://".config('mailman_host')."/mailman/admin.cgi/{$list['listname']}\">".other_icon("database_go.png", "Listen-Verwaltung aufrufen")." Verwaltung aufrufen</a>".
                    "</p>\n");
            } elseif ($list['backend'] == 'mailman3') {
                output("<p class=\"operations\">".
                    internal_link('save', icon_delete("Mailingliste löschen").' Liste löschen', "action=delete&id={$list['id']}")."<br>".
                    "<a href=\"https://".$list['urlhost']."/postorius/lists/{$list['listname']}.{$list['fqdn']}\">".other_icon("database_go.png", "Listen-Verwaltung aufrufen")." Verwaltung aufrufen</a>".
                    "</p>\n");
            }
        }
        output("</div>\n");
    }
    output("</div>");
} else {
    // keine Listen
    output('<p><em>Sie betreiben bisher keine Mailinglisten.</em></p>');
}

# 2021-11-13, Ab sofort keine neuen Mailinglisten mehr
#addnew('newlist', 'Neue Mailingliste anlegen');
output("
<p><strong>Hinweise:</strong><br />
<sup>1</sup>) Sie können später im Webinterface von Mailman einen abweichenden oder auch mehrere Verwalter eintragen. Die Information auf dieser Seite wird zyklisch synchronisiert.<br />
<sup>2</sup>) Die Größe der Archive wird in regelmäßigen Abständen eingelesen. Der hier angezeigte Wert ist möglicherweise nicht mehr aktuell.</p>\n");

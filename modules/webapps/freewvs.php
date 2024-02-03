<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('session/start.php');

require_once('freewvs.php');
require_once('webapp-installer.php');

require_role([ROLE_SYSTEMUSER]);

title("Prüfung Ihrer Web-Anwendungen");

$uid = (int) $_SESSION['userinfo']['uid'];

if (isset($_POST['freq']) && in_array($_POST['freq'], ["day","week","month"])) {
    check_form_token('freewvs_freq');
    $args = [":uid" => $uid, ":freq" => $_POST['freq']];
    db_query("REPLACE INTO qatools.freewvs (user,freq) VALUES (:uid,:freq)", $args);
    header("Location: freewvs");
    die();
}

$result = db_query("SELECT freq FROM qatools.v_freewvs WHERE uid=?", [$uid]);
$result = $result->fetch();
$freq = $result['freq'];

headline('Überprüfung Ihrer Web-Anwendungen auf Sicherheitslücken');

output('<p>Bei ' . config('company_name') . ' werden die von Ihnen installierten Web-Anwendungen (z.B. Blog-Software, Content-Management-Systeme, ...) regelmäßig automatisch auf bekannte Sicherheitsprobleme untersucht. Sie erhalten jeweils umgehend eine Nachricht, wenn wir gefährdete Anwendungen finden. Zudem werden wir Sie regelmäßig an bestehende Sicherheitslücken erinnern.</p>
<p><strong>Wie häufig möchten Sie an bestehende Sicherheitsprobleme erinnert werden?</strong></p>
' . html_form('freewvs_freq', 'freewvs', '', '<p>' . html_select('freq', ['day' => 'täglich', 'week' => 'einmal pro Woche', 'month' => 'einmal pro Monat'], $freq) . ' &#160; <input type="submit" value="speichern" /></p>'));

$results = load_results();

output('<h3>Aktuell installierte Web-Anwendungen</h3>
<p>Die folgenden Web-Anwendungen wurden beim letzten Programmdurchlauf gefunden. Diese Liste wird i.d.R. täglich aktualisiert.</p>');
foreach ($results as $app) {
    $url = get_url_for_dir($app['directory']);
    output("<div class='freewvs freewvs-{$app['state']}'>\n");
    if ($app['state'] == 'ok') {
        output("<img src='{$prefix}images/ok.png' alt='ok'>\n");
        output("<p><strong>{$app['appname']} {$app['version']}</strong></p>\n");
        output("<p>Gefunden in " . filter_output_html($app['directory']) . " (<a href=\"{$url}\">{$url}</a>)</p>\n");
        output("<p>Diese Anwendung hat keine allgemein bekannten Sicherheitsprobleme.</p>\n");
    } else {
        $vulnlink = $app['vulninfo'];
        $doclink = get_upgradeinstructions($app['appname']);
        if (substr($vulnlink, 0, 3) == 'CVE') {
            $vulnlink = 'https://cve.mitre.org/cgi-bin/cvename.cgi?name=' . $vulnlink;
        }
        output("<img src='{$prefix}images/error.png' alt='error'>\n");
        output("<p><strong>{$app['appname']} {$app['version']}</strong></p>\n");
        output("<p>Gefunden in " . filter_output_html($app['directory']) . " (<a href=\"{$url}\">{$url}</a>)</p>\n");
        if ($app['safeversion'] != '') {
            output("<p>Diese Anwendung ist von Sicherheits-Problemen betroffen. Ein <strong>Update auf Version {$app['safeversion']}</strong> wird dringend empfohlen. Prüfen Sie anhand der unten genannten Referenz welche Gefahren von dieser Anwendung momentan ausgehen.</p>\n");
        } else {
            output("<p>Diese Anwendung ist von Sicherheits-Problemen betroffen. Leider gibt es <strong>momentan keine aktualisierte Version</strong>. Prüfen Sie bitte anhand der unten genannten Beschreibung des Problem die möglichen Gefahren eines weiteren Betriebs dieser Anwendung.</p>\n");
        }
        output("<p><strong>Referenz zu diesem Sicherheitsproblem: <a href='{$vulnlink}'>{$app['vulninfo']}</a></strong></p>");
        if ($doclink != null) {
            output('<p><strong>Hinweis:</strong> Um Ihnen das Upgrade leichter zu machen, möchten wir Sie auf eine <a href="' . $doclink . '">deutschsprachige Upgrade-Anleitung</a> aufmerksam machen.</p>' . "\n");
        }
        $up = upgradeable($app['appname'], $app['version']);
        if ($up) {
            if (directory_in_use($app['directory'])) {
                output('<p><em>Automatische Update-Aktion heute nicht mehr möglich</em></p>');
            } else {
                output('<p>' . internal_link('requestupdate', 'Update automatisch durchführen', "dir={$app['directory']}&app={$up}") . "</p>\n");
            }
        }
    }
    output("</div>\n");
    #output("<tr><td>{$app['appname']} ({$app['version']})</td><td>{$app['state']}</td></tr>");
}
#output('</table>');

<?php

require_once('session/start.php');

require_once('freewvs.php');
require_once('webapp-installer.php');

require_role(array(ROLE_SYSTEMUSER));

$uid = (int) $_SESSION['userinfo']['uid'];

if (in_array($_POST['freq'],array("day","week","month"))) {
  check_form_token('freewvs_freq'); 
	db_query("REPLACE INTO qatools.freewvs (user,freq) VALUES ({$uid},'{$_POST['freq']}');");
	header("Location: freewvs");
	die();
}

$result = db_query("SELECT freq FROM qatools.v_freewvs WHERE uid={$uid};");
$result=mysql_fetch_assoc($result);
$freq=$result['freq'];

output('<h3>Überprüfung Ihrer Web-Anwendungen auf Sicherheitslücken</h3>');

output('<p>Das Programm freewvs prüft automatisch regelmäßig Ihre Web-Anwendungen (z.B. Blog-Software, Content-Management-Systeme, ...) auf bekannte Sicherheitsprobleme. Sie können festlegen, wie oft Sie bei gefundenen Problemen benachrichtigt werden möchten.</p>
<p><strong>Wie oft möchten Sie über Sicherheitsprobleme benachrichtigt werden?</strong></p>
'.html_form('freewvs_freq', '', '', '<p>'.html_select('freq', array('day' => 'täglich', 'week' => 'höchstens einmal pro Woche', 'month' => 'höchstens einmal pro Monat'), $freq).' &#160; <input type="submit" value="speichern" />').'</p>');

$results = load_results();

output('<h3>Aktuell installierte Web-Anwendungen</h3>
<p>Die folgenden Web-Anwendungen wurden beim letzten Programmdurchlauf gefunden. Diese Liste wird i.d.R. täglich aktualisiert.</p>');
foreach ($results AS $app) {
  $url = get_url_for_dir($app['directory']);
  output("<div class='freewvs freewvs-{$app['state']}'>\n");
  if ($app['state'] == 'ok') {
    output("<img src='{$prefix}images/ok.png' />\n");
    output("<p><strong>{$app['appname']} {$app['version']}</strong></p>\n");
    output("<p>Gefunden in {$app['directory']} (<a href=\"{$url}\">{$url}</a>)</p>\n");
    output("<p>Diese Anwendung ist aktuell und hat keine allgemein bekannten Sicherheitsprobleme.</p>\n");
  }
  else {
    $vulnlink = $app['vulninfo'];
    $doclink = get_upgradeinstructions($app['appname']);
    if (substr($vulnlink, 0, 3) == 'CVE') {
      $vulnlink = 'http://cve.mitre.org/cgi-bin/cvename.cgi?name='.$vulnlink;
    }
    output("<img src='{$prefix}images/error.png' />\n");
    output("<p><strong>{$app['appname']} {$app['version']}</strong></p>\n");
    output("<p>Gefunden in {$app['directory']} (<a href=\"{$url}\">{$url}</a>)</p>\n");
    if ($app['safeversion'] != '') {
      output("<p>Diese Anwendung ist von Sicherheits-Problemen betroffen. Ein <strong>Update auf Version {$app['safeversion']}</strong> wird dringend empfohlen. Prüfen Sie anhand der unten genannten Referenz welche Gefahren von dieser Anwendung momentan ausgehen.</p>\n");
    } else {
      output("<p>Diese Anwendung ist von Sicherheits-Problemen betroffen. Leider gibt es <strong>momentan keine aktualisierte Version</strong>. Prüfen Sie bitte anhand der unten genannten Beschreibung des Problem die möglichen Gefahren eines weiteren Betriebs dieser Anwendung.</p>\n");
    }
    output("<p><strong>Referenz zu diesem Sicherheitsproblem: <a href='{$vulnlink}'>{$app['vulninfo']}</a></strong></p>");
    if ($doclink != NULL)
      output('<p><strong>Hinweis:</strong> Um Ihnen das Upgrade leichter zu machen, möchten wir Sie auf eine <a href="'.$doclink.'">deutschsprachige Upgrade-Anleitung</a> aufmerksam machen.</p>'."\n");
    $up = upgradeable($app['appname'], $app['version']);
    if ($up)
    {
      output('<p>'.internal_link('requestupdate', 'Update automatisch durchführen', "dir={$app['directory']}&app={$up}")."</p>\n");
    }
  }
  output("</div>\n");
  #output("<tr><td>{$app['appname']} ({$app['version']})</td><td>{$app['state']}</td></tr>");
}
#output('</table>');


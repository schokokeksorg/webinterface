<?php

require_once('session/start.php');
require_role(array(ROLE_SYSTEMUSER));

$uid = (int) $_SESSION['userinfo']['uid'];

if (in_array($_POST['freq'],array("day","week","month"))) {
	db_query("REPLACE INTO qatools.freewvs (user,freq) VALUES ({$uid},'{$_POST['freq']}');");
	header("Location: freewvs.php");
	die();
}

output('<h3>Überprüfung Ihrer Web-Anwendungen auf Sicherheitslücken</h3>');

$result = db_query("SELECT freq FROM qatools.v_freewvs WHERE uid={$uid};");
$result=mysql_fetch_assoc($result);
$freq=$result['freq'];

output('<p>Mit dem Programm freewvs kann automatisiert geprüft werden, ob Ihre Web-Anwendungen (z.B. Blog-Software, Content-Management-Systeme, ...) noch aktuell sind oder ob es in den von Ihnen verwendeten Versionen Sicherheitslücken gibt.</p>
<p>Diese Option ermöglicht Ihnen, vollautomatisch regelmäßige Prüfungen Ihrer Webanwendungen mit Hilfe von freewvs durchzuführen. Sollten Probleme festgestellt werden, erhalten Sie Informationen darüber per E-Mail.</p>');
$form='
<div style="margin-left: 2em;">
  <p><input id="day" type="radio" name="freq" value="day" '.($freq=="day"?'checked="checked" ':"").'/><label for="day">täglich</label></p>
  <p><input id="week" type="radio" name="freq" value="week" '.($freq=="week"?'checked="checked" ':"").'/><label for="week">wöchentlich</label></p>
  <p><input id="month" type="radio" name="freq" value="month" '.($freq=="month"?'checked="checked" ':"").'/><label for="month">monatlich</label></p>
  <p><input type="submit" value="Speichern"/></p>
</div>';

output(html_form('freewvs_freq','','',$form));

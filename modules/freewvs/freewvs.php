<?php

require_once('session/start.php');
require_role(array(ROLE_SYSTEMUSER));

$uid = (int) $_SESSION['userinfo']['uid'];

if (in_array($_POST['freq'],array("day","week","month"))) {
	db_query("REPLACE INTO qatools.freewvs (user,freq) VALUES ({$uid},'{$_POST['freq']}');");
	header("Location: freewvs.php");
	die();
}

output('<h3>Web vulnerability scanner</h3>');

$result = db_query("SELECT freq FROM qatools.v_freewvs WHERE uid={$uid};");
$result=mysql_fetch_assoc($result);
$freq=$result['freq'];

output('<p>Diese Option ermöglicht Ihnen, regelmäßige Checks ihrer Webanwendungen mit Hilfe von freewvs durchzuführen.</p>');
$form='
<table>
<tr><td><input type="radio" name="freq" value="day" '.($freq=="day"?'checked="checked" ':"").'/></td><td>täglich</td></tr>
<tr><td><input type="radio" name="freq" value="week" '.($freq=="week"?'checked="checked" ':"").'/></td><td>wöchentlich</td></tr>
<tr><td><input type="radio" name="freq" value="month" '.($freq=="month"?'checked="checked" ':"").'/></td><td>monatlich</td></tr>
</table><br/>
<input type="submit" value="Speichern"/>';

output(html_form('freewvs_freq','','',$form));

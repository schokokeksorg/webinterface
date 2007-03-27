<?php

require_once('session/start.php');
/*
require_once('inc/announcement.php');


if (isset($_POST['submit']))
{
	if (save_announcement_tags())
		header("Location: userdata.php");
}
  #if (save_userdata(array('email' => $_POST['email'], 'emergency_email' => $_POST['emergency_email'])))
  #  header("Location: userdata.php");
*/
$section = "userdata";
$title = "Benutzer-Stammdaten";
include('inc/top.php');

echo '<h3>Benutzer-Stammdaten</h3>
<p>Diese Seite ist momentan leider nicht verfügbar. Sollten Sie Änderungen an Ihren hinterlegten E-Mail-Adressen wünschen, teilen Sie uns das bitte per E-Mail mit.</p>';

echo '<h3>Stammdaten</h3>
<p>Folgende Daten sind momentan bei uns hinterlegt:</p>
<table>
<tr><td>Benutzername:</td><td>'.$user['username'].'</td></tr>
<tr><td>Vollst&auml;ndiger Name:</td><td>'.$user['realname'].'</td></tr>
<tr><td>E-Mail-Adresse:</td><td>'.$user['email'].'</td></tr>
<tr><td>Notfall E-Mail-Adresse:</td><td>'.$user['emergency_email'].'</td></tr>
</table>
';
/*
echo '<h3>Benachrichtigungen</h3>
<p>Hier k&ouml;nnen Sie festlegen, welche Nachrichten Sie von uns erhalten m&ouml;chten. Bis auf schwerwiegende Nachrichten die alle Benutzer betreffen, versehen wir unsere Mitteilungen immer mit einer Angabe, welche Benutzergruppe die Nachrichten erhalten soll. Hier können Sie festlegen, welche Nachrichten Sie bekommen m&ouml;chten.</p>
<p>Mit dem Schwellenwert k&ouml;nnen Sie festlegen, ab welcher Relevanz Sie Informationen erhalten m&ouml;chten. So k&ouml;nnen Sie festlegen, ob Sie z.B. auch bei einem routinem&auml;&szlig;igen Software-Update benachrichtigt werden oder nur wenn es n&ouml;tig ist, dass Sie selbst handeln.</p>
';
echo '<form method="post">
<table>
<tr><th>&nbsp;</th><th>Beschreibung</th><th>Schwellenwert</th></tr>
';
$tags = get_all_tags();
$usertags = get_customer_tags($user['customerno']);
foreach ($tags as $tag) 
{
	echo '<tr><td><input type="checkbox" name="tag[]" value="'.$tag['name'].'"';
	if (in_array($tag['name'], array_keys($usertags)))
		echo ' checked="checked"';
	echo ' /></td><td>'.$tag['desc'].'</td><td><select name="verbosity_'.$tag['name'].'">';
	if (!isset($usertags[$tag['name']]))
		$usertags[$tag['name']] = 1;
	for ($verb = 0; $verb < 4; $verb++)
	{
		echo '<option value="'.$verb.'"';
		if ($verb == $usertags[$tag['name']])
			echo ' selected="selected"';
		echo '>'.$tag['verb'.$verb].'</option>
';
	}
	echo '</select></td></tr>
';
}
echo '</table>
<p><input type="submit" name="submit" value="&Auml;nderungen speichern" /></p>
</form>';
*/

include('inc/bottom.php');



?>

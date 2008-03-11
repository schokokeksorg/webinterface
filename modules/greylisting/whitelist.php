<?php

require_once('inc/debug.php');
require_once('inc/security.php');

require_once('greylisting.php');

$title = "Ausnahmeliste für greylisting";

require_role(ROLE_SYSTEMUSER);

$whitelist = whitelist_entries();
DEBUG($whitelist);

output("<h3>Ausnahmeliste für Greylisting</h3>
<p>Als mittlerweile sehr bewährte Methode gegen unerwünschte E-Mails (»Spam«)
setzen wir Greylisting ein. Diese Technik arbeitet sehr erfolgreich bei vergleichsweise
geringem Aufwand.</p>
<p>Ein möglicher Nachteil für den Empfänger besteht allerdings darin, dass E-Mails 
von einem eigentlich legitimen Absender, der an keinen unserer Benutzer bisher
E-Mails gesendet hat, einige Zeit verspätet zugestellt werden.</p>
<p>Sofern Sie eine derartige E-Mail erwarten, also z.B. sich auf einer fremden Website
mit Ihrer E-Mail-Adresse anmelden möchten oder ähnliches, dann können Sie hier Ihre
dafür benutzte Adresse eintragen. E-Mails an diese Adresse werden dann umgehend zugestellt.</p>
<p>Dabei können Sie Adressen wahlweise nur kurzzeitig oder dauerhaft vom Greylisting ausnehmen. 
Sie können auch lediglich einen Domainnamen angeben, dann sind sämtliche Adressen innerhalb 
dieser Domain ausgenommen.</p>
");

$form = "<table>
    <tr><th>Adresse</th><th>seit</th><th>bis</th><th> </th></tr>
    ";

foreach ($whitelist AS $entry)
{
	$end = $entry['expire'];
	if (! $end)
		$end = '<em>unbegrenzt</em>';
	$form .= "<tr><td>{$entry['local']}@{$entry['domain']}</td><td>{$entry['date']}</td><td>{$end}</td><td><a href=\"save.php?action=delete&amp;id={$entry['id']}\"><img src=\"{$prefix}images/delete.png\" alt=\"Eintrag löschen\" title=\"Diesen Eintrag löschen\" style=\"width: 16px; height: 16px;\" /></a></td></tr>\n";
}

$form .= '<tr><td><input type="text" name="address" /></td><td>-</td><td>'.html_select('expire', array('none' => 'Unbegrenzt', '5' => '5 Minuten', '10' => '10 Minuten', '20' => '20 Minuten', '30' => '30 Minuten', '60' => '1 Stunde', '120' => '2 Stunden', '1440' => '1 Tag'), '10').'</td><td></td></tr>';

$form .= '</table>';

$form .= '<p><input type="submit" value="Speichern" /></p>';

output(html_form('greylisting_add', 'save.php', 'action=add', $form));

output('<p></p>');

/*****************************
$form = "
  <table>
    <tr><th>Adresse</th><th>Verhalten</th><th>&#160;</th></tr>
    <tr><td><strong>{$vhost['fqdn']}</strong>{$mainalias}</td><td>Haupt-Adresse</td><td>&#160;</td></tr>
";

foreach ($aliases AS $alias) {
  $aliastype = 'Zusätzliche Adresse';
  if (strstr($alias['options'], 'forward')) {
    $aliastype = 'Umleitung auf Haupt-Adresse';
  }
  $formtoken = generate_form_token('aliases_toggle');
  $havewww = '<br />www.'.$alias['fqdn'].' &#160; ('.internal_link('aliasoptions.php', 'WWW-Alias entfernen', "alias={$alias['id']}&aliaswww=0&formtoken={$formtoken}").')';
  $nowww = '<br />'.internal_link('aliasoptions.php', 'Auch mit WWW', "alias={$alias['id']}&aliaswww=1&formtoken={$formtoken}");
  $wwwalias = (strstr($alias['options'], 'aliaswww') ? $havewww : $nowww);

  $to_forward = internal_link('aliasoptions.php', 'In Umleitung umwandeln', "alias={$alias['id']}&forward=1&formtoken={$formtoken}");
  $remove_forward = internal_link('aliasoptions.php', 'In zusätzliche Adresse umwandeln', "alias={$alias['id']}&forward=0&formtoken={$formtoken}");
  $typetoggle = (strstr($alias['options'], 'forward') ? $remove_forward : $to_forward);

    
  $form .= "<tr>
    <td>{$alias['fqdn']}{$wwwalias}</td>
    <td>{$aliastype}<br />{$typetoggle}</td>
    <td>".internal_link('save.php', 'Aliasname löschen', "action=deletealias&alias={$alias['id']}")."</td></tr>
  ";
}

$form .= "
<tr>
  <td>
    <strong>Neuen Aliasnamen hinzufügen</strong><br />
    <input type=\"text\" name=\"hostname\" id=\"hostname\" size=\"10\" value=\"\" />
      <strong>.</strong>".domainselect()."<br />
    <input type=\"checkbox\" name=\"options[]\" id=\"aliaswww\" value=\"aliaswww\" />
      <label for=\"aliaswww\">Auch mit <strong>www</strong> davor.</label>
  </td>
  <td>
    <select name=\"options[]\">
      <option value=\"\">zusätzliche Adresse</option>
      <option value=\"forward\">Umleitung auf Haupt-Adresse</option>
    </select>
  </td>
  <td>
    <input type=\"submit\" value=\"Hinzufügen\" />
  </td>
</tr>
</table>";

output(html_form('vhosts_add_alias', 'save.php', 'action=addalias&vhost='.$vhost['id'], $form));
    
output("<p>
  <a href=\"vhosts.php\">Zurück zur Übersicht</a>
</p>");

*/
?>

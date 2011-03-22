<?php

require_once('inc/debug.php');
require_once('inc/security.php');

require_once('greylisting.php');

title("Ausnahmeliste für Greylisting");

require_role(ROLE_SYSTEMUSER);

$whitelist = whitelist_entries();
DEBUG($whitelist);

output("<p>Als mittlerweile sehr bewährte Methode gegen unerwünschte E-Mails (»Spam«)
setzen wir Greylisting ein. Diese Technik arbeitet sehr erfolgreich bei vergleichsweise
geringem Aufwand.</p>
<p>Ein möglicher Nachteil für den Empfänger besteht allerdings darin, dass E-Mails 
von einem eigentlich legitimen Absender, der an keinen unserer Benutzer bisher
E-Mails gesendet hat, einige Zeit verspätet zugestellt werden.</p>
<p>Sofern Sie eine derartige E-Mail erwarten, also z.B. sich auf einer fremden Website
mit Ihrer E-Mail-Adresse anmelden möchten oder ähnliches, dann können Sie hier <strong>Ihre 
E-Mail-Adresse</strong> eintragen. E-Mails an diese Adresse werden dann umgehend zugestellt.</p>
<p>Dabei können Sie Adressen wahlweise nur kurzzeitig oder dauerhaft vom Greylisting ausnehmen. 
Sie können auch lediglich einen Domainnamen angeben, dann sind sämtliche Adressen innerhalb 
dieser Domain ausgenommen.</p>
");

$form = "<table>
    <tr><th>Empfänger-Adresse</th><th>seit</th><th>bis</th><th> </th></tr>
    ";

foreach ($whitelist AS $entry)
{
	$end = $entry['expire'];
	if (! $end)
		$end = '<em>unbegrenzt</em>';
  $local = $entry['local'];
  if (empty($local)) {
    $local = '*';
  }
	$form .= "<tr><td>{$local}@{$entry['domain']}</td><td>{$entry['date']}</td><td>{$end}</td><td>".internal_link("save", "<img src=\"{$prefix}images/delete.png\" alt=\"Eintrag löschen\" title=\"Diesen Eintrag löschen\" style=\"width: 16px; height: 16px;\" />", "action=delete&id={$entry['id']}")."</td></tr>\n";
}

$form .= '<tr><td><input type="text" name="address" /></td><td>-</td><td>'.html_select('expire', array('none' => 'Unbegrenzt', '30' => '30 Minuten', '60' => '1 Stunde', '720' => '12 Stunden', '1440' => '1 Tag', '2880' => '2 Tage', '10080' => '1 Woche', '43200' => '30 Tage'), '1440').'</td><td></td></tr>';

$form .= '</table>';

$form .= '<p><input type="submit" value="Speichern" /></p>';

output(html_form('greylisting_add', 'save', 'action=add', $form));

output('<p></p>');

?>

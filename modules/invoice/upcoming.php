<?php

require_once('session/start.php');

require_once('invoice.php');

require_role(ROLE_CUSTOMER);

$section = 'invoice_current';

output('<h3>offene Posten</h3>
<p>Hier sehen Sie einen Überblick über alle aktuell offenen und zukünftigen Posten.</p>');


output('<p style="margin: 1em; padding: 1em; border: 2px solid red; background: white;"><strong>Hinweis:</strong> Die hier aufgeführten Posten dienen nur Ihrer Information und erheben keinen Anspruch auf Vollständigkeit. Aus technischen Gründen sind manche Posten hier nicht aufgeführt, die dennoch berechnet werden. Zudem können, bedingt durch Rundungsfehler, die Beträge auf dieser Seite falsch dargestellt sein.</p>');

$items = upcoming_items();
$summe = 0;

$flip = true;
$today = date('Y-m-d');

output('<table><tr><th>Anzahl</th><th>Beschreibung</th><th>Zeitraum</th><th>Einzelpreis</th><th>Gesamtbetrag</th></tr>');

foreach($items AS $item)
{
	if ($flip && $item['startdatum'] > $today)
	{
		$flip = false;
		output("<tr><td colspan=\"4\" style=\"text-align: right; font-weight: bold; border: none;\">Summe bisher fällige Posten:</td>");
		output("<td style=\"font-weight: bold;\">{$summe} €</td></tr>\n");
		output("<tr><td colspan=\"5\" style=\"border: none;\"> </td></tr>\n");
	}
	$desc = $item['startdatum'];
	if ($item['enddatum'] != NULL)
		$desc = $item['startdatum'].' - '.$item['enddatum'];
	$epreis = $item['betrag'];
	if ($item['brutto'] == 0)
		$epreis = $epreis * (1 + ($item['mwst'] / 100));
	$gesamt = round($epreis * $item['anzahl'], 2);
	$epreis = round($epreis, 2);
	$summe += $gesamt;
	output("<tr><td>{$item['anzahl']}</td>");
	output("<td>{$item['beschreibung']}</td><td>{$desc}</td>");
	output("<td>{$epreis} €</td><td>{$gesamt} €</td></tr>\n");
}

output("<tr><td colspan=\"4\" style=\"text-align: right; font-weight: bold; border: none;\">Summe aller Posten:</td>");
output("<td style=\"font-weight: bold;\">{$summe} €</td></tr>\n");
output('</table><br />');


?>

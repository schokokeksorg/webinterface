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

require_once('session/start.php');

require_once('invoice.php');

require_role(ROLE_CUSTOMER);

$section = 'invoice_current';

title("Offene Posten");
output('<p>Hier sehen Sie einen Überblick über alle aktuell offenen und zukünftigen Posten.</p>');


output('<p style="margin: 1em; padding: 1em; border: 2px solid red; background: white;"><strong>Hinweis:</strong> Die hier aufgeführten Posten dienen nur Ihrer Information und erheben keinen Anspruch auf Vollständigkeit. Aus technischen Gründen sind manche Posten hier nicht aufgeführt, die dennoch berechnet werden. Zudem können, bedingt durch Rundungsfehler, die Beträge auf dieser Seite falsch dargestellt sein. Wiederkehrende Beträge werden grundsätzlich nur für den nächsten Abrechnungszeitraum angezeigt.</p>');

$items = upcoming_items();
$summe = 0;

$flip = true;
$today = date('Y-m-d');

output('<table><tr><th>Anzahl</th><th>Beschreibung</th><th>Zeitraum</th><th>Einzelpreis</th><th>Gesamtbetrag</th></tr>');

$counter = 0;

foreach($items AS $item)
{
	if ($flip && $item['startdatum'] > $today)
	{
    if ($counter == 0) {
      output("<tr><td colspan=\"5\"><em>Aktuell keine fälligen Posten</em></td></tr>");
    }
		$flip = false;
		output("<tr><td colspan=\"4\" style=\"text-align: right; font-weight: bold; border: none;\">Summe bisher fällige Posten:</td>");
		output("<td style=\"font-weight: bold;\">{$summe} €</td></tr>\n");
		output("<tr><td colspan=\"5\" style=\"border: none;\"> </td></tr>\n");
	}
  $counter++;
	$desc = $item['startdatum'];
	if ($item['enddatum'] != NULL)
		$desc = $item['startdatum'].' - '.$item['enddatum'];
	$epreis = $item['betrag'];
	if ($item['brutto'] == 0)
		$epreis = $epreis * (1 + ($item['mwst'] / 100));
	$gesamt = round($epreis * $item['anzahl'], 2);
	$epreis = round($epreis, 2);
	$summe += $gesamt;
  $einheit = ($item['einheit'] ? $item['einheit'] : '');
	output("<tr><td>{$item['anzahl']} {$einheit}</td>");
	output("<td>{$item['beschreibung']}</td><td>{$desc}</td>");
	output("<td>{$epreis} €</td><td>{$gesamt} €</td></tr>\n");
}

output("<tr><td colspan=\"4\" style=\"text-align: right; font-weight: bold; border: none;\">Summe aller Posten:</td>");
output("<td style=\"font-weight: bold;\">{$summe} €</td></tr>\n");
output('</table><br />');


?>

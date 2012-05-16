<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2012 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('session/start.php');
require_once('invoice.php');
require_once('inc/security.php');

require_role(ROLE_CUSTOMER);
$section = 'invoice_current';

title('Rechnung');
output('<p>Detailansicht Ihrer Rechnung. Beachten Sie bitte, dass diese Informationsseite sowie auch ein Ausdruck dieser Seite keine Rechnung darstellt. Ein gültiges Rechnungsdokument stellt lediglich die signierte PDF-Version bzw. eine Papierrechnung dar, die Sie von uns erhalten haben.</p>');

$invoice_id = (int) filter_input_general($_GET['id']);

output("<p>Für eine druckbare Version benutzen Sie bitte die Ausgabe ".internal_link("pdf", "als PDF-Datei", "id={$invoice_id}").".</p>
<p>&#160;</p>");


$items = invoice_items($invoice_id);
$summe = 0;

$invoice = invoice_details($invoice_id);

output('<p style="border: 1px solid black; margin: 1em; padding: 0.5em;">Rechnungsnummer: '.$invoice_id.'<br />
Kundennummer: '.$invoice['kunde'].'<br />
Rechnungsdatum: '.$invoice['datum'].'
</p>

');


output('<table><tr><th>Anzahl</th><th>Beschreibung</th><th>Einzelpreis</th><th>Gesamtbetrag</th></tr>');

foreach($items AS $item)
{
	$anzahl = $item['anzahl'];
	if (round($anzahl, 0) == $anzahl)
		$anzahl = round($anzahl, 0);
	$desc = $item['beschreibung'];
	if ($item['enddatum'] == NULL)
		$desc .= '<br />(Leistungsdatum: '.$item['datum'].')';
	else
		$desc .= '<br />(Leistungszeitraum: '.$item['datum'].' - '.$item['enddatum'].')';
	$epreis = $item['betrag'];
	if ($item['brutto'] == 0)
		$epreis = $epreis * (1 + ($item['mwst'] / 100));
  $einheit = ($item['einheit'] ? $item['einheit'] : '');
	$gesamt = round($epreis * $item['anzahl'], 2);
	$epreis = round($epreis, 2);
	$summe += $gesamt;
	output("<tr><td>{$anzahl} {$einheit}</td>");
	output("<td>{$desc}</td>");
	output("<td>{$epreis} €</td><td>{$gesamt} €</td></tr>\n");
}

output("<tr><td colspan=\"3\" style=\"text-align: right; font-weight: bold; border: none;\">Summe aller Posten:</td>");
output("<td style=\"font-weight: bold;\">{$summe} €</td></tr>\n");
output('</table><br />');


?>

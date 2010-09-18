<?php

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
	$gesamt = round($epreis * $item['anzahl'], 2);
	$epreis = round($epreis, 2);
	$summe += $gesamt;
	output("<tr><td>{$anzahl}</td>");
	output("<td>{$desc}</td>");
	output("<td>{$epreis} €</td><td>{$gesamt} €</td></tr>\n");
}

output("<tr><td colspan=\"3\" style=\"text-align: right; font-weight: bold; border: none;\">Summe aller Posten:</td>");
output("<td style=\"font-weight: bold;\">{$summe} €</td></tr>\n");
output('</table><br />');


?>

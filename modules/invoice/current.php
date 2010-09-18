<?php

require_once('session/start.php');

require_once('invoice.php');

require_role(ROLE_CUSTOMER);

title('Rechnungen');
output('<p>Hier können Sie Ihre bisherigen Rechnungen einsehen und herunterladen.</p>');

$invoices = my_invoices();

output('<table><tr><th>Nr.</th><th>Datum</th><th>Gesamtbetrag</th><th>bezahlt?</th><th>Herunterladen</th></tr>');

foreach($invoices AS $invoice)
{
	$bezahlt = 'Nein';
	if ($invoice['bezahlt'] == 1)
		$bezahlt = 'Ja';
	output("<tr><td>{$invoice['id']}</td><td>{$invoice['datum']}</td><td>{$invoice['betrag']} €</td><td>{$bezahlt}</td><td>".internal_link("pdf", "PDF", "id={$invoice['id']}").' &#160; '.internal_link("html", "HTML", "id={$invoice['id']}")."</td></tr>\n");
}

output('</table><br />

<p>'.internal_link('upcoming', 'Zukünftige Rechnungsposten anzeigen').'</p>');


?>

<?php

require_once('session/start.php');

require_once('invoice.php');

require_role(ROLE_CUSTOMER);

output('<h3>Rechnungen</h3>
<p>Hier können Sie Ihre bisherigen Rechnungen einsehen und herunterladen.</p>');

$invoices = my_invoices();

output('<table><tr><th>Nr.</th><th>Datum</th><th>Gesamtbetrag</th><th>bezahlt?</th><th>Herunterladen</th></tr>');

foreach($invoices AS $invoice)
{
	$bezahlt = 'Nein';
	if ($invoice['bezahlt'] == 1)
		$bezahlt = 'Ja';
	output("<tr><td>{$invoice['id']}</td><td>{$invoice['datum']}</td><td>{$invoice['betrag']} €</td><td>{$bezahlt}</td><td><a href=\"pdf.php?id={$invoice['id']}\">PDF</a> &#160; <a href=\"html.php?id={$invoice['id']}\">HTML</a></td></tr>\n");
}

output('</table><br />');


?>

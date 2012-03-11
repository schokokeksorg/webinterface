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

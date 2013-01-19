<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2013 by schokokeks.org Hosting, namely
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

$show_paid = (isset($_GET['paid']) && $_GET['paid'] == '1');

$invoices = my_invoices();

$invoices_to_show = array();
foreach ($invoices as $i) {
  if ($show_paid || $i['bezahlt'] == 0) {
    array_push($invoices_to_show, $i);
  }
}

if (count($invoices_to_show) == 0) {
  $error = 'Keine Rechnungen gefunden.';
  if (count($invoices) == 0) {
    $error = 'Bisher keine Rechnungen vorhanden.';
  } else {
    $error = 'Keine offenen Rechnungen vorhanden. Klicken Sie auf den nachstehenden Link um bereits bezahlte Rechnungen zu sehen.';
  }
  if ($show_paid) {
  }

  output('<p><em>'.$error.'</em></p>');
} else {
  if ($show_paid) {
    output('<p>Hier können Sie Ihre bisherigen Rechnungen einsehen und herunterladen.</p>');
  } else {
    output('<p>Hier sehen Sie Ihre momentan offenen Rechnungen. Ältere, bereits bezahlte Rechnungen können sie über den untenstehenden Link einblenden.</p>');
  }
  output('<table><tr><th>Nr.</th><th>Datum</th><th>Gesamtbetrag</th><th>bezahlt?</th><th>Herunterladen</th></tr>');

  foreach($invoices_to_show AS $invoice)
  {
	  $bezahlt = 'Nein';
    $class = 'unpaid';
  	if ($invoice['bezahlt'] == 1) {
	  	$bezahlt = 'Ja';
      $class = 'paid';
    }
  	output("<tr class=\"{$class}\"><td>".internal_link("html", $invoice['id'], "id={$invoice['id']}")."</td><td>{$invoice['datum']}</td><td>{$invoice['betrag']} €</td><td>{$bezahlt}</td><td>".internal_link("pdf", "PDF", "id={$invoice['id']}").' &#160; '.internal_link("html", "HTML", "id={$invoice['id']}")."</td></tr>\n");
  }

  output('</table><br />');
}

if (! $show_paid) {
  output('<p>'.internal_link('', 'Bereits bezahlte Rechnungen zeigen', 'paid=1').'</p>');
}
output('<p>'.internal_link('upcoming', 'Zukünftige Rechnungsposten anzeigen').'</p>');



?>

<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('session/start.php');
require_once('invoice.php');
require_once('inc/security.php');

require_role(ROLE_CUSTOMER);

$invoice_id = (int) $_GET['id'];

$items = invoice_items($invoice_id);

$invoice = invoice_details($invoice_id);


$outformat = 'html';
if (isset($_REQUEST['out']) && $_REQUEST['out'] == 'pdf') {
    $outformat = 'pdf';
}


// Footer
$footer = '<footer>
<table width="100%">
<tr><td>schokokeks.org GbR<br>
Bernd Wurst / Johannes Böck<br>
www.schokokeks.org<br>
root@schokokeks.org</td>

<td>Steuernummer 51072/01109<br>
Finanzamt Backnang<br>
USt-ID: DE255720588</td>

<td>Volksbank Backnang<br>
IBAN: DE91 6029 1120 0041 5120 06<br>
BIC: GENODES1VBK<br>
(Kto: 41512 006 / BLZ: 602 911 20)</td>
</tr>
</table></footer>';




$html = '<html><head><title>Rechnung Nr. '.$invoice_id.'</title>
<style type="text/css">

@media (min-width: 22cm) {
    body {
    	margin: 2cm;
	    margin-top: 0.5cm;
        width: 17cm;
    }
}

@media print,dompdf {
    html {
        margin: 0;
        padding: 0;
    }
    @page {
        margin: 0;
        padding: 0;
    }
    body {
        margin: 2cm;
        margin-bottom: 3cm;
    }
}

body {
    font-family: "DejaVu Sans", sans-serif;
    font-size: 8pt;
    padding: 0;
    text-align: justify;
}

table, th, td, tr {
    font-size: 8pt;
    margin: 0;
    padding: 0;
    border: none;
    border-collapse: collapse;
    vertical-align: top;
}
th {
    text-align: left;
    border-bottom: 1px solid black;
}

@media print,dompdf {
    #header,
    footer {
        position: fixed;
        left: 0;
	    right: 0;
    	font-size: 8pt;
    }
}

#header {
  top: 0;
}

footer {
  border-top: 1px solid #000;
}

@media print,dompdf {
    footer {
        position: absolute;
  bottom: -2cm;
    }
}

#header table,
footer table {
	width: 100%;
	border-collapse: collapse;
	border: none;
}

#header td,
footer td {
  padding: 0;
	width: 33%;
}

.page-number {
  text-align: center;
}

.page-number:before {
  content: "Seite " counter(page);
}

#addressfield {
    float: left;
    position: relative;
    top: 3cm;
    left: 0cm;
    width: 8.5cm;
    min-height: 5cm;
}
#addressfield p {
    margin-left: 0.5cm;
    margin-top: 0.3cm;
    font-size: 11pt;
}

#addressfieldheader {
    font-size: 8pt;
    padding-left: 0.2cm;
    width: 100%;
    border-bottom: 0.1pt solid black;
}


#rightcolumn {
  float: right;
  width: 6cm;
}



#rightcolumn img {
    position: relative;
    top: 0;
    width: 4.08cm;
    height: 3cm;
    margin: 0;
    padding: 0;
}

td {
  font-size: 8pt;
}

p {
    margin-bottom: 0.6cm;
}

tr.even {
    background-color: #eee;
}


</style>
</head>
<body>
';
// Folding Marks
$html .= '
<div style="position: absolute; left: -2cm; top: 8.5cm; width: 0.5cm; height: 1px; border-top: 1px solid black;"></div>
<div style="position: absolute; left: -2cm; top: 19cm; width: 0.5cm; height: 1px; border-top: 1px solid black;"></div>
<div style="position: absolute; left: -2cm; top: 12.85cm; width: 0.6cm; height: 1px; border-top: 1px solid black;"></div>
';

if ($outformat == 'pdf') {
    $html .= $footer;
}

$address = invoice_address($invoice['kunde']);

// Address Field
$html .= '<div id="addressfield">
<div id="addressfieldheader">schokokeks.org · Köchersberg 32 · 71540 Murrhardt</div>
<p>'.($address['company'] != null ? $address['company'].'<br>' : '').$address['name'].'<br>
'.$address['address'].'<br>
'.($address['country'] != 'DE' ? $address['country'].'-' : '').$address['zip'].' '.$address['city'].'
</p>
</div>';
// Right col
$html .= '<div id="rightcolumn">
<img src="'.($outformat == 'pdf' ? '.' : '../..').'/themes/default/images/schokokeks.png">
<p style="margin-bottom: 0.2cm;"><strong>schokokeks.org GbR</strong><br>
Bernd Wurst / Johannes Böck<br>
Köchersberg 32<br>
71540 Murrhardt</p>
<p>Tel: 07192-936432<br>
E-Mail: root@schokokeks.org</p>
</div>';

// Caption / Invoice-Details
$html .= '
<table style="width: 100%; clear: both;">
<tr><td style="width: 11cm; font-size: 11pt; font-weight: bold;">Rechnung</td><td>
  <table>
  <tr><td colspan="2"><strong>Bei Fragen bitte immer angeben:</strong></td></tr>
  <tr><td>Rechnungsdatum:</td><td align="right">'.date("d. m. Y", strtotime($invoice['datum'])).'</td></tr>
  <tr><td>Rechnungsnummer:</td><td align="right">'.$invoice_id.'</td></tr>
  <tr><td>Kundennummer:</td><td align="right">'.$invoice['kunde'].'</td></tr>
  </table>
  </td></tr>
</table>';

$anrede = 'Sehr geehrte Damen und Herren';

$parts = explode(' ', $address['name']);
$nachname = array_pop($parts);

if ($address['name']) {
    if ($address['salutation'] == 'Herr') {
        $anrede = 'Sehr geehrter Herr '.$nachname;
    } elseif ($address['salutation'] == 'Frau') {
        $anrede = 'Sehr geehrte Frau '.$nachname;
    }
}

// Salutation
$html .= '<p>'.$anrede.',<br>
hiermit stellen wir die nachfolgend genannten Posten in Rechnung.</p>';


// Table
$html .= '<table style="width: 100%;">
<tr style="border-bottom: 1px solid black;">
<th style="width: 1cm; text-align: center;">Anz.</th>
<th style="width: 2cm;">&nbsp;</th>
<th>Beschreibung</th>
<th style="width: 2.5cm; text-align: center;">Einzelpreis</th>
<th style="width: 3cm; text-align: center;">Gesamtpreis</th></tr>';

$vattype = null;
// An der ersten Zeile entscheidet sich, ob die gesamte Rechnung als Netto- oder Bruttorechnung erstellt wird
$einzelsummen = [];
$summe = 0.0;

$odd = true;
foreach ($items as $item) {
    if ($vattype == 'gross' && $item['brutto'] == 0) {
        system_failure('Mixed gross and net positions');
    } elseif ($vattype == 'net' && $item['brutto'] == 1) {
        system_failure('Mixed gross and net positions');
    } else {
        $vattype = ($item['brutto'] == 1 ? 'gross' : 'net');
    }

    $anzahl = $item['anzahl'];
    if (round($anzahl, 0) == $anzahl) {
        $anzahl = round($anzahl, 0);
    }
    $desc = $item['beschreibung'];
    if ($item['enddatum'] == null) {
        $desc .= '<br />(Leistungsdatum: '.$item['datum'].')';
    } else {
        $desc .= '<br />(Leistungszeitraum: '.$item['datum'].' - '.$item['enddatum'].')';
    }
    $epreis = $item['betrag'];
    if ($item['brutto'] == 0) {
        $epreis = $epreis * (1 + ($item['mwst'] / 100));
    }
    $einheit = ($item['einheit'] ? $item['einheit'] : '');
    $gesamt = $epreis * $item['anzahl'];
    $epreis = $epreis;

    if (array_key_exists($item['mwst'], $einzelsummen)) {
        $einzelsummen[$item['mwst']]['net'] += $gesamt / (1 + ($item['mwst'] / 100));
        $einzelsummen[$item['mwst']]['vat'] += $gesamt / (1 + ($item['mwst'] / 100)) * ($item['mwst'] / 100);
        $einzelsummen[$item['mwst']]['gross'] += $gesamt;
    } else {
        $einzelsummen[$item['mwst']] = ['net' => $gesamt / (1 + ($item['mwst'] / 100)),
                                             'vat' => $gesamt / (1 + ($item['mwst'] / 100)) * ($item['mwst'] / 100),
                                             'gross' => $gesamt, ];
    }
    $summe += $gesamt;

    $html .= "<tr class='".($odd ? 'odd' : 'even')."'><td style='text-align: right;'>{$anzahl}</td><td>{$einheit}</td>";
    $html .= "<td>{$desc}</td>";
    $html .= "<td style='text-align: right;'>".number_format($epreis, 2, ',', '.')." €</td><td style='text-align: right;'>".number_format($gesamt, 2, ',', '.')." €</td></tr>\n";
    $odd = !$odd;
}
$html .= '<tr><td>&nbsp;</td></tr>';

foreach ($einzelsummen as $percent => $sums) {
    $html .= '<tr><td colspan="4" style="text-align: right;">Nettobetrag ('.number_format($percent, 1, ',', '.').'% MwSt):</td><td style="text-align: right;">'.number_format($sums['net'], 2, ',', '.').' €</td></tr>
              <tr><td colspan="4" style="text-align: right;">MwSt-Betrag '.number_format($percent, 1, ',', '.').'%:</td><td style="text-align: right;">'.number_format($sums['vat'], 2, ',', '.').' €</td></tr>';
    if (count($einzelsummen) > 1) {
        $html .= '<tr><td colspan="4" style="text-align: right;">Brutto-Teilbetrag '.number_format($percent, 1, ',', '.').'% MwSt:</td><td style="text-align: right;">'.number_format($sums['gross'], 2, ',', '.').' €</td></tr>';
    }
}
$html .= '<tr style="font-weight: bold;"><td colspan="4" style="text-align: right;">Rechnungsbetrag:</td><td style="text-align: right;">'.number_format($summe, 2, ',', '.').' €</td></tr>
</table>';

// Disclaimer
if ($invoice['abbuchung'] == 1) {
    $sepamandat = get_sepamandat($invoice['sepamandat']);
    $iban = substr($sepamandat['iban'], 0, 8) . '**********' . substr($sepamandat['iban'], -4);
    $display_iban = $iban;
    for ($i = strlen($iban) - (strlen($iban) % 4) ; $i != 0 ; $i -= 4) {
        $display_iban = substr($display_iban, 0, $i) . ' ' . substr($display_iban, $i);
    }
    $html .= '<p><strong>Bitte nicht überweisen!</strong> Der fällige Betrag wird gemäß dem von Ihnen erteilten 
    Lastschrift-Mandat in wenigen Tagen vom Konto mit der IBAN '.$display_iban.' bei der '.$sepamandat['bankname'].' 
    (BIC: '.$sepamandat['bic'].') abgebucht. Diese Kontodaten beruhen auf dem Mandat Nr. '.$sepamandat['mandatsreferenz'].' 
    vom '.$sepamandat['erteilt'].'. Unsere Gläubiger-ID lautet '.$sepamandat['glaeubiger_id'].'.</p>';
} else {
    $html .= '<p>Bitte begleichen Sie diese Rechnung umgehend nach Erhalt ohne Abzüge auf das unten angegebene Konto. Geben Sie
im Verwendungszweck Ihrer Überweisung bitte die Rechnungsnummer '.$invoice_id.' an, damit Ihre Buchung korrekt
zugeordnet werden kann.</p>';
}


$html .= '<p>Wir danken Ihnen, dass Sie unser Angebot in Anspruch genommen haben und hoffen weiterhin auf eine gute
Zusammenarbeit. Dieser Rechnung liegen die Allgemeinen Geschäftsbedingungen zum Zeitpunkt des
Rechnungsdatums zugrunde, die Sie unter https://www.schokokeks.org/agb abrufen können.</p>';


if ($outformat == 'html') {
    $html .= $footer;
}

$html .= '</body>
</html>';

// Composer's auto-loading functionality
require "vendor/autoload.php";

use Dompdf\Dompdf;

//generate some PDFs!
$dompdf = new DOMPDF();  //if you use namespaces you may use new \DOMPDF()
$dompdf->setPaper('A4', 'portrait');
$dompdf->loadHtml($html);
$dompdf->render();

if ($outformat == 'pdf') {
    $dompdf->stream("sample.pdf", ["Attachment" => 0]);
} else {
    echo $html;
}




/*');



output("<tr><td colspan=\"3\" style=\"text-align: right; font-weight: bold; border: none;\">Summe aller Posten:</td>");
output("<td style=\"font-weight: bold;\">{$summe} €</td></tr>\n");
output('</table><br />');

$l = get_lastschrift($invoice_id);

if ($invoice['bezahlt'] == 1) {
    output('<p>Diese Rechnung ist bereits bezahlt.</p>');
} elseif ($l && $l['status'] == 'pending') {
    output('<p>Diese Rechnung wird am '.$l['buchungsdatum'].' per Lastschrift eingezogen.</p>');
} elseif ($l && $l['status'] == 'done') {
    output('<p>Diese Rechnung wurde am '.$l['buchungsdatum'].' per Lastschrift eingezogen.</p>');
} else {
    $qrcode_image = generate_qrcode_image_invoice($invoice_id);

    output('<h4>GiroCode für Mobile Banking (SEPA Credit Transfer)</h4><p><img src="data:image/png;base64,'.base64_encode($qrcode_image).'" /></p>');
}*/

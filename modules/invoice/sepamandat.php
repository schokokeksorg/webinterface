<?php

/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/icons.php');
require_once('invoice.php');

require_once('inc/javascript.php');
javascript();

require_role(ROLE_CUSTOMER);
$section = 'invoice_current';

title('Erteilung eines Mandats zur SEPA-Basis-Lastschrift');

output('<p>Ich ermächtige die Firma schokokeks.org GbR, Zahlungen von meinem Konto mittels Lastschrift
einzuziehen. Zugleich weise ich mein Kreditinstitut an, die von der Firma schokokeks.org GbR auf mein
Konto gezogenen Lastschriften einzulösen.</p>
<p>Hinweis: Ich kann innerhalb von acht Wochen, beginnend mit dem Belastungsdatum, die Erstattung des
belasteten Betrages verlangen. Es gelten dabei die mit meinem Kreditinstitut vereinbarten Bedingungen.
Insbesondere fallen bei Zurückweisung einer gerechtfertigten Abbuchung i.d.R. Gebühren an.</p>');

$name = $_SESSION['customerinfo']['name'];
if ($_SESSION['customerinfo']['company']) {
    if ($_SESSION['customerinfo']['name']) {
        $name = $_SESSION['customerinfo']['company'] . ' / ' . $_SESSION['customerinfo']['name'];
    } else {
        $name = $_SESSION['customerinfo']['company'];
    }
}
output('<p>Dieses Mandat gilt für Forderungen bzgl. der Kundennummer <strong>' . $_SESSION['customerinfo']['customerno'] . '</strong> (' . $name . '). Sämtliche Forderungen werden mindestens 2 Tage vor Fälligkeit angekündigt. Diese Ankündigung erfolgt in der Regel im Rahmen der Zusendung einer Rechnung per E-Mail.</p>');



$first_date = date('Y-m-d');
$invoices = my_invoices();
foreach ($invoices as $i) {
    if ($i['bezahlt'] == 0 && $i['sepamandat'] == null && $i['datum'] < $first_date) {
        $first_date = $i['datum'];
    }
}

$html = '<h4>Gültigkeit des Mandats</h4>
<p>Ein eventuell zuvor erteiltes Mandat wird zu diesem Datum automatisch ungültig.</p>';

$checked = false;
if ($first_date != date('Y-m-d')) {
    $checked = true;
    $html .= '<p><input type="radio" id="gueltig_ab_' . $first_date . '" name="gueltig_ab" value="' . $first_date . '" checked="checked" /><label for="gueltig_ab_' . $first_date . '">Dieses Mandat gilt <strong>ab ' . $first_date . '</strong> (Alle bisher offenen Forderungen werden ebenfalls abgebucht)</label></p>';
}
$html .= '<p><input type="radio" id="gueltig_ab_heute" name="gueltig_ab" value="' . date('Y-m-d') . '" ' . ($checked ? '' : 'checked="checked"') . ' /><label for="gueltig_ab_heute">Dieses Mandat gilt <strong>ab heute</strong> (' . date('Y-m-d') . ')</label></p>';
$html .= '<p><input type="radio" id="gueltig_ab_auswahl" name="gueltig_ab" value="datum" /><label for="gueltig_ab_datum">Dieses Mandat gilt <strong>erst ab</strong></label> <input type="date" id="gueltig_ab_datum" name="gueltig_ab_datum" value="' . date('Y-m-d') . '">';


$html .= '<h4>Ihre Bankverbindung</h4>';
$html .= '<table>
<tr><td><label for="kontoinhaber">Name des Kontoinhabers:</label></td><td><input type="text" name="kontoinhaber" id="kontoinhaber" /> <button id="copydata">Von Kundendaten kopieren</button></td></tr>
<tr><td><label for="adresse">Adresse des Kontoinhabers:</label></td><td><textarea cols="50" lines="2" name="adresse" id="adresse"></textarea></td></tr>
<tr id="ktoblz_input" style="display: none;"><td>Kontodaten:</td><td><label for="kto">Konto:</label> <input type="text" id="kto" /> <label for="blz">BLZ:</label> <input type="text" id="blz" /><br /><button id="ktoblz">IBAN berechnen...</button></td></tr>
<tr><td><label for="iban">IBAN:</label></td><td><input type="text" name="iban" id="iban" size="30" /><span id="iban_feedback"></span><br />
<span id="ktoblz_button"><button id="showktoblz">IBAN aus Kontonummer / BLZ berechnen...</button></span>
</td></tr>
<tr><td><label for="bankname">Name der Bank:</label></td><td><input type="text" name="bankname" id="bankname" size="30" /></td></tr>
<tr><td><label for="bic">BIC:</label></td><td><input type="text" name="bic" id="bic" /></td></tr>
</table>';

$html .= '<p><input type="submit" value="Mandat erteilen" /></p>';


output(html_form('sepamandat_neu', 'save', 'action=new', $html));

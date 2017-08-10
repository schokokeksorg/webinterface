<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2014 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/icons.php');
require_once('invoice.php');

require_once('inc/jquery.php');

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
    $name = $_SESSION['customerinfo']['company'] .' / '. $_SESSION['customerinfo']['name'];
  } else {
    $name = $_SESSION['customerinfo']['company'];
  }
}
output('<p>Dieses Mandat gilt für Forderungen bzgl. der Kundennummer <strong>'.$_SESSION['customerinfo']['customerno'].'</strong> ('.$name.'). Sämtliche Forderungen werden mindestens 2 Tage vor Fälligkeit angekündigt. Diese Ankündigung erfolgt in der Regel im Rahmen der Zusendung einer Rechnung per E-Mail.</p>');



$first_date = date('Y-m-d');
$invoices = my_invoices();
foreach ($invoices as $i) {
  if ($i['bezahlt'] == 0 && $i['sepamandat'] == NULL && $i['datum'] < $first_date) {
    $first_date = $i['datum'];
  }
}

$html = '<h4>Gültigkeit des Mandats</h4>
<p>Ein eventuell zuvor erteiltes Mandat wird zu diesem Datum automatisch ungültig.</p>';

$checked = False;
if ($first_date != date('Y-m-d')) {
  $checked = True;
  $html .= '<p><input type="radio" id="gueltig_ab_'.$first_date.'" name="gueltig_ab" value="'.$first_date.'" checked="checked" /><label for="gueltig_ab_'.$first_date.'">Dieses Mandat gilt <strong>ab '.$first_date.'</strong> (Alle bisher offenen Forderungen werden ebenfalls abgebucht)</label></p>';
}
$html .= '<p><input type="radio" id="gueltig_ab_heute" name="gueltig_ab" value="'.date('Y-m-d').'" '.($checked ? '' : 'checked="checked"').' /><label for="gueltig_ab_heute">Dieses Mandat gilt <strong>ab heute</strong> ('.date('Y-m-d').')</label></p>';
$html .= '<p><input type="radio" id="gueltig_ab_datum" name="gueltig_ab" value="datum" /><label for="gueltig_ab_datum">Dieses Mandat gilt <strong>erst ab</strong></label> '.html_datepicker("gueltig_ab_datum", time()).'</p>';

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

output('
<script type="text/javascript">

function populate_bankinfo(result) {
  bank = result[0];
  if (bank.iban_ok == 1) {
    $("#iban_feedback").html(\''.icon_ok().'\');
    if ($(\'#bankname\').val() == "") 
      $(\'#bankname\').val(bank.bankname);
    if ($(\'#bic\').val() == "")  
      $(\'#bic\').val(bank.bic);
  } else {
    $("#iban_feedback").html(\''.icon_error('IBAN scheint nicht gültig zu sein').'\');
    $(\'#bankname\').val("");
    $(\'#bic\').val("");
  }
    
}

function searchbank() 
{
  var iban = $(\'#iban\').val().replace(/\s/g, \'\');
  if (iban.substr(0,2) == "DE" && iban.length == 22) {
    $("#bankname").prop("disabled", true);
    $("#bic").prop("disabled", true);
    $.getJSON("sepamandat_banksearch?iban="+iban, populate_bankinfo)
      .always( function() {
        $("#bankname").prop("disabled", false);
        $("#bic").prop("disabled", false);
      });
  } else {
    $("#iban_feedback").html("");
  }
}

function copydata_worker( result ) {
  $("#kontoinhaber").val(result.kundenname);
  $("#adresse").val(result.adresse);
}

function copydata( event ) {
  event.preventDefault();
  var kunde = $.getJSON("sepamandat_copydata", copydata_worker);
}

function populate_iban(result) {
  info = result[0];
  $("#iban").val(info.iban);
  populate_bankinfo(result)
}

function ktoblz( event ) {
  event.preventDefault();
  var kto = $("#kto").val();
  var blz = $("#blz").val();
  $.getJSON("sepamandat_banksearch?kto="+kto+"&blz="+blz, populate_iban)
}

function showktoblz( event ) {
  event.preventDefault();
  $("#ktoblz_button").hide();
  $("#ktoblz_input").show();
}



$(\'#iban\').on("change keyup paste", searchbank );
$("#copydata").click(copydata);
$("#showktoblz").click(showktoblz);
$("#ktoblz").click(ktoblz);

</script>
');
?>

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

require_once('inc/base.php');
require_once('inc/security.php');

function my_invoices()
{
  $c = (int) $_SESSION['customerinfo']['customerno'];
  $result = db_query("SELECT id,datum,betrag,bezahlt,abbuchung FROM kundendaten.ausgestellte_rechnungen WHERE kunde={$c} ORDER BY id DESC");
  $ret = array();
  while($line = mysql_fetch_assoc($result))
  	array_push($ret, $line);
  return $ret;
}


function get_pdf($id)
{
  $c = (int) $_SESSION['customerinfo']['customerno'];
  $id = (int) $id;
  $result = db_query("SELECT pdfdata FROM kundendaten.ausgestellte_rechnungen WHERE kunde={$c} AND id={$id}");
  if (mysql_num_rows($result) == 0)
	system_failure('Ungültige Rechnungsnummer oder nicht eingeloggt');
  return mysql_fetch_object($result)->pdfdata;

}


function invoice_details($id)
{
  $c = (int) $_SESSION['customerinfo']['customerno'];
  $id = (int) $id;
  $result = db_query("SELECT kunde,datum,betrag,bezahlt,abbuchung FROM kundendaten.ausgestellte_rechnungen WHERE kunde={$c} AND id={$id}");
  if (mysql_num_rows($result) == 0)
  	system_failure('Ungültige Rechnungsnummer oder nicht eingeloggt');
  return mysql_fetch_assoc($result);
}

function invoice_items($id)
{
  $c = (int) $_SESSION['customerinfo']['customerno'];
  $id = (int) $id;
  $result = db_query("SELECT id, beschreibung, datum, enddatum, betrag, einheit, brutto, mwst, anzahl FROM kundendaten.rechnungsposten WHERE rechnungsnummer={$id} AND kunde={$c}");
  if (mysql_num_rows($result) == 0)
  	system_failure('Ungültige Rechnungsnummer oder nicht eingeloggt');
  $ret = array();
  while($line = mysql_fetch_assoc($result))
  array_push($ret, $line);
  return $ret;
}


function upcoming_items()
{
  $c = (int) $_SESSION['customerinfo']['customerno'];
  $result = db_query("SELECT anzahl, beschreibung, startdatum, enddatum, betrag, einheit, brutto, mwst FROM kundendaten.upcoming_items WHERE kunde={$c} ORDER BY startdatum ASC");
  $ret = array();
  while($line = mysql_fetch_assoc($result))
	  array_push($ret, $line);
  return $ret;
}


function generate_qrcode_image($id) 
{
  $invoice = invoice_details($id);
  $customerno = $invoice['kunde'];
  $amount = 'EUR'.sprintf('%.2f', $invoice['betrag']);
  $datum = $invoice['datum'];
  $data = 'BCD
001
1
SCT
GENODES1VBK
schokokeks.org GbR
DE91602911200041512006
'.$amount.'


RE '.$id.' KD '.$customerno.' vom '.$datum.'
Rechnung '.$id.' von schokokeks.org';
  
  $descriptorspec = array(
    0 => array("pipe", "r"),  // STDIN ist eine Pipe, von der das Child liest
    1 => array("pipe", "w"),  // STDOUT ist eine Pipe, in die das Child schreibt
    2 => array("pipe", "w") 
  );

  $process = proc_open('qrencode -t PNG -o - -l M', $descriptorspec, $pipes);

  if (is_resource($process)) {
    // $pipes sieht nun so aus:
    // 0 => Schreibhandle, das auf das Child STDIN verbunden ist
    // 1 => Lesehandle, das auf das Child STDOUT verbunden ist

    fwrite($pipes[0], $data);
    fclose($pipes[0]);

    $pngdata = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    // Es ist wichtig, dass Sie alle Pipes schließen bevor Sie
    // proc_close aufrufen, um Deadlocks zu vermeiden
    $return_value = proc_close($process);
  
    return $pngdata;
  } else {
    warning('Es ist ein interner Fehler im Webinterface aufgetreten, aufgrund dessen kein QR-Code erstellt werden kann. Sollte dieser Fehler mehrfach auftreten, kontaktieren Sie bitte die Administratoren.');
  }
}


function generate_bezahlcode_image($id) 
{
  $invoice = invoice_details($id);
  $customerno = $invoice['kunde'];
  $amount = str_replace('.', '%2C', sprintf('%.2f', $invoice['betrag']));
  $datum = $invoice['datum'];
  $data = 'bank://singlepaymentsepa?name=schokokeks.org%20GbR&reason=RE%20'.$id.'%20KD%20'.$customerno.'%20vom%20'.$datum.'&iban=DE91602911200041512006&bic=GENODES1VBK&amount='.$amount;
  
  $descriptorspec = array(
    0 => array("pipe", "r"),  // STDIN ist eine Pipe, von der das Child liest
    1 => array("pipe", "w"),  // STDOUT ist eine Pipe, in die das Child schreibt
    2 => array("pipe", "w") 
  );

  $process = proc_open('qrencode -t PNG -o -', $descriptorspec, $pipes);

  if (is_resource($process)) {
    // $pipes sieht nun so aus:
    // 0 => Schreibhandle, das auf das Child STDIN verbunden ist
    // 1 => Lesehandle, das auf das Child STDOUT verbunden ist

    fwrite($pipes[0], $data);
    fclose($pipes[0]);

    $pngdata = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    // Es ist wichtig, dass Sie alle Pipes schließen bevor Sie
    // proc_close aufrufen, um Deadlocks zu vermeiden
    $return_value = proc_close($process);
  
    return $pngdata;
  } else {
    warning('Es ist ein interner Fehler im Webinterface aufgetreten, aufgrund dessen kein QR-Code erstellt werden kann. Sollte dieser Fehler mehrfach auftreten, kontaktieren Sie bitte die Administratoren.');
  }
}



# bank://singlepaymentsepa?name=SCHOKOKEKS.ORG%20GBR&reason=RE%20256%20KD%2032%20vom%202008-03-01&iban=DE91602911200041512006&bic=GENODES1VBK&amount=45%2C00
?>

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

require_once('inc/base.php');
require_once('inc/security.php');

include("external/php-iban/php-iban.php");


function my_invoices()
{
  $c = (int) $_SESSION['customerinfo']['customerno'];
  $result = db_query("SELECT id,datum,betrag,bezahlt,abbuchung,sepamandat FROM kundendaten.ausgestellte_rechnungen WHERE kunde=? ORDER BY id DESC", array($c));
  $ret = array();
  while($line = $result->fetch())
  	array_push($ret, $line);
  return $ret;
}


function get_pdf($id)
{
  $c = (int) $_SESSION['customerinfo']['customerno'];
  $id = (int) $id;
  $result = db_query("SELECT pdfdata FROM kundendaten.ausgestellte_rechnungen WHERE kunde=:c AND id=:id",array(":c" => $c, ":id" => $id));
  if ($result->rowCount() == 0)
	  system_failure('Ungültige Rechnungsnummer oder nicht eingeloggt');
  return $result->fetch(PDO::FETCH_OBJ)->pdfdata;

}


function invoice_details($id)
{
  $c = (int) $_SESSION['customerinfo']['customerno'];
  $id = (int) $id;
  $result = db_query("SELECT kunde,datum,betrag,bezahlt,abbuchung FROM kundendaten.ausgestellte_rechnungen WHERE kunde=:c AND id=:id",array(":c" => $c, ":id" => $id));
  if ($result->rowCount() == 0)
  	system_failure('Ungültige Rechnungsnummer oder nicht eingeloggt');
  return $result->fetch();
}

function invoice_items($id)
{
  $c = (int) $_SESSION['customerinfo']['customerno'];
  $id = (int) $id;
  $result = db_query("SELECT id, beschreibung, datum, enddatum, betrag, einheit, brutto, mwst, anzahl FROM kundendaten.rechnungsposten WHERE rechnungsnummer=:id AND kunde=:c",array(":c" => $c, ":id" => $id));
  if ($result->rowCount() == 0)
  	system_failure('Ungültige Rechnungsnummer oder nicht eingeloggt');
  $ret = array();
  while($line = $result->fetch())
  array_push($ret, $line);
  return $ret;
}


function upcoming_items()
{
  $c = (int) $_SESSION['customerinfo']['customerno'];
  $result = db_query("SELECT anzahl, beschreibung, startdatum, enddatum, betrag, einheit, brutto, mwst FROM kundendaten.upcoming_items WHERE kunde=? ORDER BY startdatum ASC", array($c));
  $ret = array();
  while($line = $result->fetch())
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


RE '.$id.' KD '.$customerno.' vom '.$datum;
  
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

function get_lastschrift($rechnungsnummer) {
  $rechnungsnummer = (int) $rechnungsnummer;
  $result = db_query("SELECT rechnungsnummer, rechnungsdatum, sl.betrag, buchungsdatum FROM kundendaten.sepalastschrift sl LEFT JOIN kundendaten.ausgestellte_rechnungen re ON (re.id=sl.rechnungsnummer) WHERE rechnungsnummer=? AND re.abbuchung=1", array($rechnungsnummer));
  if ($result->rowCount() == 0) {
    return NULL;
  }
  $item = $result->fetch();
  return $item;
}

function get_lastschriften($mandatsreferenz)
{
  $result = db_query("SELECT rechnungsnummer, rechnungsdatum, betrag, buchungsdatum FROM kundendaten.sepalastschrift WHERE mandatsreferenz=? ORDER BY buchungsdatum DESC", array($mandatsreferenz));
  $ret = array();
  while ($item = $result->fetch()) {
    $ret[] = $item;
  }
  return $ret;
}

function get_sepamandate() 
{
  $cid = (int) $_SESSION['customerinfo']['customerno'];
  $result = db_query("SELECT id, mandatsreferenz, glaeubiger_id, erteilt, medium, gueltig_ab, gueltig_bis, erstlastschrift, kontoinhaber, adresse, iban, bic, bankname FROM kundendaten.sepamandat WHERE kunde=?", array($cid));
  $ret = array();
  while ($entry = $result->fetch()) {
    array_push($ret, $entry);
  }
  return $ret;
}


function yesterday($date) 
{
  $result = db_query("SELECT ? - INTERVAL 1 DAY", array($date));
  return $result->fetch()[0];
}


function invalidate_sepamandat($id, $date) 
{
  $args = array(":cid" => (int) $_SESSION['customerinfo']['customerno'],
                ":id" => (int) $id,
                ":date" => $date);
  db_query("UPDATE kundendaten.sepamandat SET gueltig_bis=:date WHERE id=:id AND kunde=:cid", $args);
}


function sepamandat($name, $adresse, $iban, $bankname, $bic, $gueltig_ab)
{
  $cid = (int) $_SESSION['customerinfo']['customerno'];

  $first_date = date('Y-m-d');
  $invoices = my_invoices();
  foreach ($invoices as $i) {
    if ($i['bezahlt'] == 0 && $i['datum'] < $first_date) {
      $first_date = $i['datum'];
    }
  }
  if ($gueltig_ab < date('Y-m-d') && $gueltig_ab != $first_date) {
    system_failure('Das Mandat kann nicht rückwirkend erteilt werden. Bitte geben Sie ein Datum in der Zukunft an.');
  }
  $alte_mandate = get_sepamandate();
  $referenzen = array();
  foreach ($alte_mandate as $mandat) {
    if ($mandat['gueltig_bis'] == NULL || $mandat['gueltig_bis'] >= $gueltig_ab) {
      DEBUG('Altes Mandat wird für ungültig erklärt.');
      DEBUG($mandat);
      invalidate_sepamandat($mandat['id'], yesterday($gueltig_ab));
    }
    array_push($referenzen, $mandat['mandatsreferenz']);
  }
  $counter = 1;
  $referenz = sprintf('K%04d-M%03d', $cid, $counter);
  while (in_array($referenz, $referenzen)) {
    $counter++;
    $referenz = sprintf('K%04d-M%03d', $cid, $counter);
  }
  DEBUG('Nächste freie Mandatsreferenz: '. $referenz);

  $glaeubiger_id = config('glaeubiger_id');

  $today = date('Y-m-d');
  db_query("INSERT INTO kundendaten.sepamandat (mandatsreferenz, glaeubiger_id, kunde, erteilt, medium, gueltig_ab, kontoinhaber, adresse, iban, bic, bankname) VALUES (:referenz, :glaeubiger_id, :cid, :today, 'online', :gueltig_ab, :name, :adresse, :iban, :bic, :bankname)",
          array(":referenz" => $referenz, ":glaeubiger_id" => $glaeubiger_id, ":cid" => $cid, 
                ":today" => $today, ":gueltig_ab" => $gueltig_ab, ":name" => $name, ":adresse" => $adresse, 
                ":iban" => $iban, ":bic" => $bic, ":bankname" => $bankname));
}



function get_bank_info($iban) 
{
  if (strlen($iban) != 22 || substr($iban, 0, 2) != 'DE') {
    // Geht nur bei deutschen IBANs
    echo 'Fehler!';
    echo '$iban = '.$iban;
    echo 'strlen($iban): '.strlen($iban);
    echo 'substr($iban, 0, 2): '.substr($iban, 0, 2);
    return NULL;
  }
  $blz = substr($iban, 4, 8);
  // FIXME: Liste der BLZs muss vorhanden sein!
  $bankinfofile = dirname(__FILE__).'/bankinfo.txt';
  $f = file($bankinfofile);
  $match = '';
  foreach ($f as $line) {
    if (substr($line, 0, 9) == $blz.'1') {
      $match = $line;
      break;
    }
  }
  $bank = array();
  $bank['name'] = iconv('latin1', 'utf8', chop(substr($match, 9,58)));
  $bank['bic'] = chop(substr($match, 139,11));
  return $bank;
}


function find_iban($blz, $kto) 
{
  $iban = sprintf('DE00%08s%010s', $blz, $kto);
  $iban = iban_set_checksum($iban);
  return $iban;
}


?>

<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2018 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/base.php');
require_once('inc/security.php');

use_module('contacts');
require_once('contacts.php');

function my_invoices()
{
    $c = (int) $_SESSION['customerinfo']['customerno'];
    $result = db_query("SELECT id,datum,betrag,bezahlt,abbuchung,sepamandat FROM kundendaten.ausgestellte_rechnungen WHERE kunde=? ORDER BY id DESC", [$c]);
    $ret = [];
    while ($line = $result->fetch()) {
        array_push($ret, $line);
    }
    return $ret;
}


function get_pdf($id)
{
    $c = (int) $_SESSION['customerinfo']['customerno'];
    $id = (int) $id;
    $result = db_query("SELECT pdfdata FROM kundendaten.ausgestellte_rechnungen WHERE kunde=:c AND id=:id", [":c" => $c, ":id" => $id]);
    if ($result->rowCount() == 0) {
        system_failure('Ungültige Rechnungsnummer oder nicht eingeloggt');
    }
    return $result->fetch(PDO::FETCH_OBJ)->pdfdata;
}


function invoice_address($customer = null)
{
    $c = (int) $_SESSION['customerinfo']['customerno'];
    if ($customer != null && have_role(ROLE_SYSADMIN)) {
        $c = (int) $customer;
    }
    $result = db_query("SELECT contact_kunde, contact_rechnung FROM kundendaten.kunden WHERE id=?", [$c]);
    $kontakte = $result->fetch();
    $kunde = get_contact($kontakte['contact_kunde'], $c);
    if ($kontakte['contact_rechnung']) {
        $rechnung = get_contact($kontakte['contact_rechnung'], $c);
        foreach (['company', 'name', 'address', 'zip', 'city', 'country', 'email'] as $field) {
            if ($rechnung[$field]) {
                $kunde[$field] = $rechnung[$field];
            }
        }
    }
    // Hier ist $kunde der bereinigte Rechnungskontakt
    return $kunde;
}


function invoice_details($id)
{
    $id = (int) $id;
    $result = db_query("SELECT kunde,datum,betrag,bezahlt,sepamandat,abbuchung FROM kundendaten.ausgestellte_rechnungen WHERE id=:id", [":id" => $id]);
    if ($result->rowCount() == 0) {
        system_failure('Ungültige Rechnungsnummer oder nicht eingeloggt');
    }
    $data = $result->fetch();
    if (!have_role(ROLE_SYSADMIN) && $data['kunde'] != (int) $_SESSION['customerinfo']['customerno']) {
        system_failure('Ungültige Rechnungsnummer für diesen Login');
    }
    return $data;
}

function invoice_items($id)
{
    $id = (int) $id;
    $result = db_query("SELECT id, beschreibung, datum, enddatum, betrag, einheit, brutto, mwst, anzahl FROM kundendaten.rechnungsposten WHERE rechnungsnummer=:id", [":id" => $id]);
    if ($result->rowCount() == 0) {
        system_failure('Ungültige Rechnungsnummer oder nicht eingeloggt');
    }
    $ret = [];
    while ($line = $result->fetch()) {
        array_push($ret, $line);
    }
    return $ret;
}


function upcoming_items()
{
    $c = (int) $_SESSION['customerinfo']['customerno'];
    $result = db_query("SELECT quelle, id, anzahl, beschreibung, startdatum, enddatum, betrag, einheit, brutto, mwst FROM kundendaten.upcoming_items WHERE kunde=? ORDER BY startdatum ASC", [$c]);
    $ret = [];
    while ($line = $result->fetch()) {
        array_push($ret, $line);
    }
    return $ret;
}


function generate_qrcode_image_invoice($id)
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

    $descriptorspec = [
    0 => ["pipe", "r"],  // STDIN ist eine Pipe, von der das Child liest
    1 => ["pipe", "w"],  // STDOUT ist eine Pipe, in die das Child schreibt
    2 => ["pipe", "w"],
  ];

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


function get_lastschrift($rechnungsnummer)
{
    $rechnungsnummer = (int) $rechnungsnummer;
    $result = db_query("SELECT rechnungsnummer, rechnungsdatum, sl.betrag, buchungsdatum, sl.status FROM kundendaten.sepalastschrift sl LEFT JOIN kundendaten.ausgestellte_rechnungen re ON (re.sepamandat=sl.mandatsreferenz) WHERE rechnungsnummer=?", [$rechnungsnummer]);
    if ($result->rowCount() == 0) {
        return null;
    }
    $item = $result->fetch();
    return $item;
}

function get_lastschriften($mandatsreferenz)
{
    $result = db_query("SELECT rechnungsnummer, rechnungsdatum, betrag, buchungsdatum, status FROM kundendaten.sepalastschrift WHERE mandatsreferenz=? ORDER BY buchungsdatum DESC", [$mandatsreferenz]);
    $ret = [];
    while ($item = $result->fetch()) {
        $ret[] = $item;
    }
    return $ret;
}


function get_sepamandat($id)
{
    $result = db_query("SELECT id, kunde, mandatsreferenz, glaeubiger_id, erteilt, medium, gueltig_ab, gueltig_bis, erstlastschrift, kontoinhaber, adresse, iban, bic, bankname FROM kundendaten.sepamandat WHERE id=? OR mandatsreferenz=?", [$id, $id]);
    return $result->fetch();
}

function get_sepamandate()
{
    $cid = (int) $_SESSION['customerinfo']['customerno'];
    $result = db_query("SELECT id, mandatsreferenz, glaeubiger_id, erteilt, medium, gueltig_ab, gueltig_bis, erstlastschrift, kontoinhaber, adresse, iban, bic, bankname FROM kundendaten.sepamandat WHERE kunde=?", [$cid]);
    $ret = [];
    while ($entry = $result->fetch()) {
        array_push($ret, $entry);
    }
    return $ret;
}


function yesterday($date)
{
    $result = db_query("SELECT ? - INTERVAL 1 DAY", [$date]);
    return $result->fetch()[0];
}


function invalidate_sepamandat($id, $date)
{
    $args = [":cid" => (int) $_SESSION['customerinfo']['customerno'],
                ":id" => (int) $id,
                ":date" => $date, ];
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
    $referenzen = [];
    foreach ($alte_mandate as $mandat) {
        if ($mandat['gueltig_bis'] == null || $mandat['gueltig_bis'] >= $gueltig_ab) {
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
    db_query(
        "INSERT INTO kundendaten.sepamandat (mandatsreferenz, glaeubiger_id, kunde, erteilt, medium, gueltig_ab, kontoinhaber, adresse, iban, bic, bankname) VALUES (:referenz, :glaeubiger_id, :cid, :today, 'online', :gueltig_ab, :name, :adresse, :iban, :bic, :bankname)",
        [":referenz" => $referenz, ":glaeubiger_id" => $glaeubiger_id, ":cid" => $cid,
                ":today" => $today, ":gueltig_ab" => $gueltig_ab, ":name" => $name, ":adresse" => $adresse,
                ":iban" => $iban, ":bic" => $bic, ":bankname" => $bankname, ]
    );
    db_query(
        "UPDATE kundendaten.ausgestellte_rechnungen SET abbuchung=1 WHERE kunde = :cid AND datum >= :gueltig_ab and bezahlt=0",
        [":cid" => $cid, ":gueltig_ab" => $gueltig_ab]
    );
}



function get_bank_info($iban)
{
    if (strlen($iban) != 22 || substr($iban, 0, 2) != 'DE') {
        // Geht nur bei deutschen IBANs
        echo 'Fehler!';
        echo '$iban = '.$iban;
        echo 'strlen($iban): '.strlen($iban);
        echo 'substr($iban, 0, 2): '.substr($iban, 0, 2);
        return null;
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
    $bank = [];
    $bank['name'] = iconv('latin1', 'utf8', chop(substr($match, 9, 58)));
    $bank['bic'] = chop(substr($match, 139, 11));
    return $bank;
}


function find_iban($blz, $kto)
{
    $iban = sprintf('DE00%08s%010s', $blz, $kto);
    $iban = iban_set_checksum($iban);
    return $iban;
}


function get_customerquota()
{
    $cid = (int) $_SESSION['customerinfo']['customerno'];
    $result = db_query("SELECT quota FROM system.customerquota WHERE cid=:cid", [":cid" => $cid]);
    $data = $result->fetch();
    return $data["quota"];
}

function save_more_storage($items, $storage)
{
    $cid = (int) $_SESSION['customerinfo']['customerno'];

    $queries = [];

    if ($storage < 1024 || $storage > 10240) {
        input_error('Speicherplatz nicht im erwarteten Bereich');
    }
    $oldcustomerquota = get_customerquota();
    if ($oldcustomerquota > 102400) {
        # Über 100 GB soll die Automatik nichts machen
        system_failure("Ihr Speicherplatz kann über diese Funktion nicht weiter erhöht werden. Bitte wenden Sie sich an die Administratoren.");
    }
    $result = db_query("SELECT quota FROM system.customerquota WHERE cid=:cid AND lastchange > CURDATE()", [":cid" => $cid]);
    if ($result->rowcount() > 0) {
        system_failure("Ihr Speicherplatz wurde heute bereits verändert. Sie können dies nur einmal am Tag machen.");
    }

    $queries[] = ["UPDATE system.customerquota SET quota=quota+:storage WHERE cid=:cid", [":storage" => $storage, ":cid" => $cid]];

    foreach ($items as $data) {
        if ($data['anzahl'] == 0) {
            continue;
        }
        $data['kunde'] = $cid;
        $data['notizen'] = 'Bestellt via Webinterface';
        if (!isset($data['anzahl']) ||
        !isset($data['beschreibung']) ||
        !isset($data['datum']) ||
        !array_key_exists('kuendigungsdatum', $data) ||
        !isset($data['betrag']) ||
        !isset($data['monate'])) {
            DEBUG($data);
            input_error("Ungültige Daten");
            return;
        }

        $param = [];
        foreach ($data as $k => $v) {
            $param[':'.$k] = $v;
        }

        $queries[] = ["INSERT INTO kundendaten.leistungen (kunde,periodisch,beschreibung,datum,kuendigungsdatum,betrag,brutto,monate,anzahl,notizen) VALUES ".
                       "(:kunde,1,:beschreibung,:datum,:kuendigungsdatum,:betrag,:brutto,:monate,:anzahl,:notizen)", $param, ];
    }

    if (count($queries) < 2) {
        system_failure("irgendwas stimmt jetzt nicht");
    }

    foreach ($queries as $q) {
        db_query($q[0], $q[1]);
    }
    $name = $_SESSION['customerinfo']['company'];
    if (! $name && $_SESSION['customerinfo']['name']) {
        $name = $_SESSION['customerinfo']['name'];
    }
    $allstorage = $oldcustomerquota+$storage;
    $emailaddr = $_SESSION['customerinfo']['email'];
    $message = "Hallo,\n\nsoeben wurde im Webinterface von ".config('company_name')." eine Bestellung über zusätzlichen Speicherplatz ausgeführt.\nSollten Sie diese Bestellung nicht getätigt haben, antworten Sie bitte auf diese E-Mail um unseren Support zu erreichen.\n\nBei dieser Bestellung wurden {$storage} MB zusätzlicher Speicherplatz bestellt. Ihnen stehen ab sofort insgesamt {$allstorage} MB zur Verfügung.\n\nIhre Kundennummer: {$_SESSION['customerinfo']['customerno']} ({$name})\n";
    send_mail($emailaddr, 'Auftragsbestätigung: Mehr Speicherplatz bei schokokeks.org', $message);
}

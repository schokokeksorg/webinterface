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

require_role(ROLE_CUSTOMER);

require('invoice.php');
require_once("inc/debug.php");
require_once("inc/security.php");
global $debugmode;

$section = 'invoice_current';


if ($_GET['action'] == 'new') {
    check_form_token('sepamandat_neu');

    $gueltig_ab = $_REQUEST['gueltig_ab'];
    if ($gueltig_ab == 'datum') {
        $gueltig_ab = $_REQUEST['gueltig_ab_datum_year'].'-'.$_REQUEST['gueltig_ab_datum_month'].'-'.$_REQUEST['gueltig_ab_datum_day'];
    }
    if (! check_date($gueltig_ab)) {
        system_failure('Konnte das Datum nicht auslesen');
    }
    DEBUG('Gültig ab: '.$gueltig_ab);

    if (empty($_REQUEST['kontoinhaber'])) {
        system_failure('Bitte geben Sie den Kontoinhaber so an, wie dies bei Ihrer Bank hinterlegt ist.');
    }
    $name = $_REQUEST['kontoinhaber'];
    DEBUG('Kontoinhaber:'.$name);

    if (empty($_REQUEST['adresse'])) {
        system_failure('Bitte geben Sie die Adresse des Kontoinhabers an.');
    }
    $adresse = $_REQUEST['adresse'];
    DEBUG('Adresse: '.$adresse);

    if (empty($_REQUEST['iban'])) {
        system_failure('Es wurde keine IBAN angegeben.');
    }
    $iban = str_replace(' ', '', strtoupper($_REQUEST['iban']));
    if (! verify_iban($iban)) {
        system_failure("Die IBAN scheint nicht korrekt zu sein!");
    }
    DEBUG('IBAN: '.$iban);

    $bankname = $_REQUEST['bankname'];
    if (empty($_REQUEST['bankname'])) {
        system_failure('Bitte geben Sie den Namen der Bank an.');
    }
    DEBUG('Bank: '.$bankname);

    $bic = null;
    if (empty($_REQUEST['bic'])) {
        if (substr($iban, 0, 2) == 'DE') {
            $bic=null;
        } else {
            system_failure('Sie haben keinen BIC angegeben. Für Konten außerhalb Deutschlands ist ein BIC weiterhin erforderlich.');
        }
    } else {
        $bic = $_REQUEST['bic'];
    }
    DEBUG('BIC: '.$bic);


    sepamandat($name, $adresse, $iban, $bankname, $bic, $gueltig_ab);

    redirect($prefix.'go/invoice/current');
}

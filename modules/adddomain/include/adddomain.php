<?php

/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/debug.php');
require_once('inc/base.php');
require_once('inc/security.php');
require_once('inc/error.php');

require_once('httpnet.php');

require_once('modules/email/include/vmail.php');


function get_domain_offer($domainname)
{
    $domainname = filter_input_hostname($domainname);
    $domainname = preg_replace('/^www\./', '', $domainname);

    $basename = preg_replace('/([^\.]+)\..*$/', '\1', $domainname);
    DEBUG('Found Basename: ' . $basename);
    $tld = preg_replace('/^[^\.]*\./', '', $domainname);
    DEBUG('Found TLD: ' . $tld);

    $cid = (int) $_SESSION['customerinfo']['customerno'];

    $result = db_query("SELECT id FROM kundendaten.domains WHERE domainname=:domainname AND tld=:tld", ["domainname" => $basename, "tld" => $tld]);
    if ($result->rowCount() != 0) {
        warning('Diese Domain ist in unserem System bereits vorhanden und kann daher nicht noch einmal eingetragen werden.');
        return;
    }

    $data = ["domainname" => $domainname, "basename" => $basename, "tld" => $tld];

    $result = db_query("SELECT tld, gebuehr, setup FROM misc.domainpreise_kunde WHERE kunde=:cid AND tld=:tld AND ruecksprache='N'", [":cid" => $cid, ":tld" => $tld]);
    if ($result->rowCount() != 1) {
        $result = db_query("SELECT tld, gebuehr, setup FROM misc.domainpreise WHERE tld=:tld AND ruecksprache='N'", [":tld" => $tld]);
    }
    if ($result->rowCount() != 1) {
        warning('Die Endung »' . $tld . '« steht zur automatischen Eintragung nicht zur Verfügung.');
        return;
    }
    $temp = $result->fetch();
    $data["gebuehr"] = $temp["gebuehr"];
    $data["setup"] = ($temp["setup"] ? $temp["setup"] : 0.0);

    $available = api_domain_available($domainname);
    if (!$available) {
        warning('Die Domain »' . $domainname . '« ist leider nicht verfügbar.');
        return;
    }
    return $data;
}



function register_domain($domainname, $uid)
{
    $data = get_domain_offer($domainname);

    if (!$data) {
        // Die Include-Datei setzt eine passende Warning-Nachricht
        show_warnings();
        system_failure('Interner Fehler');
    }

    $cid = (int) $_SESSION['customerinfo']['customerno'];
    $useraccount = null;
    $available_users = list_useraccounts();
    foreach ($available_users as $u) {
        if ($uid == $u['uid']) {
            $useraccount = (int) $uid;
            break;
        }
    }
    if (!$useraccount) {
        system_failure('Kein User gesetzt');
    }

    $args = [":cid" => $cid,
        ":useraccount" => $useraccount,
        ":basename" => $data['basename'],
        ":tld" => $data['tld'], ];
    db_query("INSERT INTO kundendaten.domains (kunde, useraccount, domainname, tld, billing, registrierungsdatum, dns,webserver, mail) VALUES " .
           "(:cid, :useraccount, :basename, :tld, 'regular', NULL, 1, 1, 'auto') ", $args);
    $domid = db_insert_id();
    /*if ($data['setup']) {
      $args = array(":cid" => $cid, ":setup" => $data['setup'], ":text" => 'Einmalige Setup-Gebühren für Domain "'.$data['domainname'].'"');
      db_query("INSERT INTO kundendaten.leistungen (kunde,periodisch,datum,betrag,brutto,beschreibung,anzahl) VALUES (:cid, 0, CURDATE(), :setup, 1, :text, 1)", $args);
    }*/
    # Umstellen auf vmail
    change_domain($domid, 'virtual');
}

function list_useraccounts()
{
    $customerno = (int) $_SESSION['customerinfo']['customerno'];
    $result = db_query("SELECT uid,username,name FROM system.useraccounts WHERE kunde=?", [$customerno]);
    $ret = [];
    while ($item = $result->fetch()) {
        $ret[] = $item;
    }
    DEBUG($ret);
    return $ret;
}

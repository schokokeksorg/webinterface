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

require_once('inc/debug.php');
require_once('inc/db_connect.php');
require_once('inc/base.php');
require_once('inc/security.php');
require_once('inc/error.php');

require_once('terions.php');


function get_domain_offer($domainname) 
{
  $domainname = filter_input_hostname($domainname);
  $domainname = preg_replace('/^www\./', '', $domainname);

  $basename = preg_replace('/([^\.]+)\..*$/', '\1', $domainname);
  DEBUG('Found Basename: '.$basename);
  $tld = preg_replace('/^[^\.]*\./', '', $domainname);
  DEBUG('Found TLD: '.$tld);

  $cid = (int) $_SESSION['customerinfo']['customerno'];

  $data = array("domainname" => $domainname, "basename" => $basename, "tld" => $tld);

  $result = db_query("SELECT tld, gebuehr, setup FROM misc.domainpreise_kunde WHERE kunde={$cid} AND tld='{$tld}' AND ruecksprache='N'");
  if (mysql_num_rows($result) != 1) {
    $result = db_query("SELECT tld, gebuehr, setup FROM misc.domainpreise WHERE tld='{$tld}' AND ruecksprache='N'");
  }
  if (mysql_num_rows($result) != 1) {
    warning('Die Endung »'.$tld.'« steht zur automatischen Eintragung nicht zur Verfügung.');
    return;
  }
  $temp = mysql_fetch_assoc($result);
  $data["gebuehr"] = $temp["gebuehr"];
  $data["setup"] = ($temp["setup"] ? $temp["setup"] : 0.0);
  
  $available = terions_available($domainname);
  if (! $available) {
    warning('Die Domain »'.$domainname.'« ist leider nicht verfügbar.');
    return;
  }
  return $data;
}



function register_domain($domainname, $uid)
{
  $data = get_domain_offer($domainname);

  if (! $data) {
    // Die Include-Datei setzt eine passende Warning-Nachricht
    show_warnings();
    system_failure('Interner Fehler');
  }

  $cid = (int) $_SESSION['customerinfo']['customerno'];
  $useraccount = NULL;
  $available_users = list_useraccounts();
  foreach ($available_users as $u) {
    if ($uid == $u['uid']) {
      $useraccount = (int) $uid;
      break;
    } 
  }
  if (! $useraccount) {
    system_failure('Kein User gesetzt');
  }

  db_query("INSERT INTO kundendaten.domains (kunde, useraccount, domainname, tld, billing, registrierungsdatum, dns,webserver, mail, provider, betrag, brutto) VALUES ({$cid}, {$useraccount}, '{$data['basename']}', '{$data['tld']}', 'regular', NULL, 1, 1, 'auto', 'terions', {$data['gebuehr']}, 1) ");
  if ($data['setup']) {
    db_query("INSERT INTO kundendaten.leistungen (kunde,periodisch,datum,betrag,brutto,beschreibung,anzahl) VALUES ({$cid}, 0, CURDATE(), {$data['setup']}, 1, 'Einmalige Setup-Gebühren für Domain \"{$data['domainname']}\"', 1)");
  }
}

function list_useraccounts()
{
  $customerno = (int) $_SESSION['customerinfo']['customerno'];
  $result = db_query("SELECT uid,username,name FROM system.useraccounts WHERE kunde={$customerno}");
  $ret = array();
  while ($item = mysql_fetch_assoc($result))
  {
    $ret[] = $item;
  }
  DEBUG($ret);
  return $ret;
}


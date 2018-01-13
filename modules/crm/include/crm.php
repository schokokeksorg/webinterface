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


function find_customers($string) 
{
  $string = db_escape_string(chop($string));
  $return = array();
  $result = db_query("SELECT k.id FROM kundendaten.kunden AS k LEFT JOIN kundendaten.kundenkontakt AS kk ".
                     "ON (kk.kundennr = k.id) LEFT JOIN system.useraccounts AS u ON (k.id=u.kunde) WHERE ".
                     "firma LIKE '%{$string}%' OR firma2 LIKE '%{$string}%' OR ".
                     "nachname LIKE '%{$string}%' OR vorname LIKE '%{$string}%' OR ".
                     "adresse LIKE '%{$string}%' OR adresse2 LIKE '%{$string}%' OR ".
                     "ort LIKE '%{$string}%' OR pgp_id LIKE '%{$string}%' OR ".
                     "notizen LIKE '%{$string}%' OR kk.name LIKE '%{$string}%' OR ".
                     "kk.wert LIKE '%{$string}%' OR u.name LIKE '%{$string}%' OR ".
                     "u.username LIKE '%{$string}%' OR k.id='{$string}' OR u.uid='{$string}';");
  while ($entry = $result->fetch())
    $return[] = $entry['id'];

  return $return;
}


function find_users_for_customer($id)
{
  $id = (int) $id;
  $return = array();
  $result = db_query("SELECT uid, username FROM system.useraccounts WHERE ".
                     "kunde='{$id}';");
  while ($entry = $result->fetch())
    $return[$entry['uid']] = $entry['username'];

  return $return;
}



function hosting_contracts($cid)
{
  $cid = (int) $cid;
  $result = db_query("SELECT u.username, werber, beschreibung, betrag, brutto, monate, anzahl, startdatum, startdatum + INTERVAL laufzeit MONTH - INTERVAL 1 DAY AS mindestlaufzeit, kuendigungsdatum, gesperrt, notizen FROM kundendaten.hosting AS h LEFT JOIN system.useraccounts AS u ON (h.hauptuser=u.uid) WHERE h.kunde=".$cid);
  $ret = array();
  while ($x = $result->fetch())
    array_push($ret, $x);
  DEBUG($ret);

}







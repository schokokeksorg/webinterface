<?php

require_once('inc/base.php');


function find_customers($string) 
{
  $string = mysql_real_escape_string(chop($string));
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
  while ($entry = mysql_fetch_assoc($result))
    $return[] = $entry['id'];

  return $return;
}


function find_users_for_customer($id)
{
  $id = (int) $id;
  $return = array();
  $result = db_query("SELECT uid, username FROM system.useraccounts WHERE ".
                     "kunde='{$id}';");
  while ($entry = mysql_fetch_assoc($result))
    $return[$entry['uid']] = $entry['username'];

  return $return;
}



function hosting_contracts($cid)
{
  $cid = (int) $cid;
  $result = db_query("SELECT u.username, werber, beschreibung, betrag, brutto, monate, anzahl, startdatum, startdatum + INTERVAL laufzeit MONTH - INTERVAL 1 DAY AS mindestlaufzeit, kuendigungsdatum, gesperrt, notizen FROM kundendaten.hosting AS h LEFT JOIN system.useraccounts AS u ON (h.hauptuser=u.uid) WHERE h.kunde=".$cid);
  $ret = array();
  while ($x = mysql_fetch_assoc($result))
    array_push($ret, $x);
  DEBUG($ret);

}







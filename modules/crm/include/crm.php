<?php

require_once('inc/base.php');


function find_customer($string) 
{
  $string = mysql_real_escape_string(chop($string));
  $return = array();
  $result = db_query("SELECT id FROM kundendaten.kunden WHERE ".
                     "firma LIKE '%{$string}%' OR firma2 LIKE '%{$string}%' OR ".
                     "nachname LIKE '%{$string}%' OR vorname LIKE '%{$string}%' OR ".
                     "adresse LIKE '%{$string}%' OR adresse2 LIKE '%{$string}%' OR ".
                     "ort LIKE '%{$string}%' OR pgp_id LIKE '%{$string}%' OR ".
                     "notizen LIKE '%{$string}%';");
  while ($entry = mysql_fetch_assoc($result))
    $return[] = $entry['id'];

  $result = db_query("SELECT kundennr FROM kundendaten.kundenkontakt WHERE ".
                     "wert LIKE '%{$string}%' OR name LIKE '%{$string}%';");
  while ($entry = mysql_fetch_assoc($result))
    $return[] = $entry['kundennr'];
  
  return $return;
}






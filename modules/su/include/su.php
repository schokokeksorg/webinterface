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

function list_system_users()
{
  require_role(ROLE_SYSADMIN);

  $result = db_query("SELECT uid,username FROM system.v_useraccounts ORDER BY username");
  
  $ret = array();
  while ($item = mysql_fetch_object($result))
    array_push($ret, $item);
  return $ret;
}


function list_customers()
{
  require_role(ROLE_SYSADMIN);

  $result = db_query("SELECT id, IF(firma IS NULL, CONCAT_WS(' ', vorname, nachname), CONCAT(firma, ' (', CONCAT_WS(' ', vorname, nachname), ')')) AS name FROM kundendaten.kunden");
  
  $ret = array();
  while ($item = mysql_fetch_object($result))
    array_push($ret, $item);
  return $ret;
}


function find_customers($string) 
{
  $string = mysql_real_escape_string(chop($string));
  $return = array();
  $result = db_query("SELECT k.id FROM kundendaten.kunden AS k LEFT JOIN system.useraccounts AS u ON (k.id=u.kunde) WHERE ".
                     "firma LIKE '%{$string}%' OR firma2 LIKE '%{$string}%' OR ".
                     "nachname LIKE '%{$string}%' OR vorname LIKE '%{$string}%' OR ".
                     "adresse LIKE '%{$string}%' OR adresse2 LIKE '%{$string}%' OR ".
                     "ort LIKE '%{$string}%' OR pgp_id LIKE '%{$string}%' OR ".
                     "notizen LIKE '%{$string}%' OR email_rechnung LIKE '%{$string}%' OR ".
                     "email LIKE '%{$string}%' OR email_extern LIKE '%{$string}%' OR u.name LIKE '%{$string}%' OR ".
                     "u.username LIKE '%{$string}%' OR k.id='{$string}' OR u.uid='{$string}';");
  while ($entry = mysql_fetch_assoc($result))
    $return[] = $entry['id'];

  $result = db_query("SELECT kunde FROM kundendaten.domains WHERE kunde IS NOT NULL AND (
                      domainname LIKE '%{$string}%' OR CONCAT_WS('.', domainname, tld) LIKE '%{$string}%'
                      )");

  while ($entry = mysql_fetch_assoc($result))
    $return[] = $entry['kunde'];

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








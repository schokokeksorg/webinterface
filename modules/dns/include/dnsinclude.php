<?php

require_once('inc/debug.php');
require_once('inc/db_connect.php');
require_once('inc/base.php');
require_once('inc/security.php');

require_once('class/domain.php');


function get_dyndns_accounts() 
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT * FROM dns.dyndns WHERE uid={$uid}");
  $list = array();
  while ($item = mysql_fetch_assoc($result)) {
    array_push($list, $item);
  }
  DEBUG($list);
  return $list;
}


function get_dyndns_account($id) 
{
  $id = (int) $id;
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT * FROM dns.dyndns WHERE id={$id} AND uid={$uid}");
  if (mysql_num_rows($result) != 1) {
    logger("modules/dns/include/dnsinclude.php", "dyndns", "account »{$id}« invalid for uid »{$uid}«.");
    system_failure("Account ungültig");
  }
  $item = mysql_fetch_assoc($result);
  DEBUG($item);
  return $item;
}


function create_dyndns_account($handle, $password_http, $sshkey)
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $handle = maybe_null(mysql_real_escape_string(filter_input_username($handle)));
  $sshkey = maybe_null(mysql_real_escape_string(filter_input_general($sshkey)));

  $pwhash = 'NULL';
  if ($password_http)
    $pwhash = "'{SHA}".base64_encode(sha1($password_http, true))."'";

  db_query("INSERT INTO dns.dyndns (uid, handle, password, sshkey) VALUES ({$uid}, {$handle}, {$pwhash}, {$sshkey})");
  logger("modules/dns/include/dnsinclude.php", "dyndns", "inserted account");
}


function edit_dyndns_account($id, $handle, $password_http, $sshkey)
{
  $id = (int) $id;
  $handle = maybe_null(mysql_real_escape_string(filter_input_username($handle)));
  $sshkey = maybe_null(mysql_real_escape_string(filter_input_general($sshkey)));

  $pwhash = 'NULL';
  if ($password_http)
    $pwhash = "'{SHA}".base64_encode(sha1($password_http, true))."'";

  db_query("UPDATE dns.dyndns SET handle={$handle}, password={$pwhash}, sshkey={$sshkey} WHERE id={$id} LIMIT 1");
  logger("modules/dns/include/dnsinclude.php", "dyndns", "edited account »{$id}«");
}


function delete_dyndns_account($id)
{
  $id = (int) $id;

  db_query("DELETE FROM dns.dyndns WHERE id={$id} LIMIT 1");
  logger("modules/dns/include/dnsinclude.php", "dyndns", "deleted account »{$id}«");
}


function get_dyndns_records($id)
{
  $id = (int) $id;
  $result = db_query("SELECT hostname, domain, type, ttl, lastchange, id FROM dns.custom_records WHERE dyndns={$id}");
  $data = array();
  while ($entry = mysql_fetch_assoc($result)) {
    $dom = new Domain((int) $entry['domain']);
    $entry['fqdn'] = $entry['hostname'].'.'.$dom->fqdn;
    if (! $entry['hostname'])
      $entry['fqdn'] = $dom->fqdn;
    array_push($data, $entry);
  }
  DEBUG($data);
  return $data;
}



function get_domain_records($dom)
{
  $dom = (int) $dom;
  $result = db_query("SELECT hostname, domain, type, ip, dyndns, data, ttl, id FROM dns.custom_records WHERE domain={$dom}");
  $data = array();
  while ($entry = mysql_fetch_assoc($result)) {
    $dom = new Domain((int) $entry['domain']);
    $entry['fqdn'] = $entry['hostname'].'.'.$dom->fqdn;
    if (! $entry['hostname'])
      $entry['fqdn'] = $dom->fqdn;
    array_push($data, $entry);
  }
  DEBUG($data);
  return $data;
}



?>

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
    logger(LOG_WARNING, "modules/dns/include/dnsinclude", "dyndns", "account »{$id}« invalid for uid »{$uid}«.");
    system_failure("Account ungültig");
  }
  $item = mysql_fetch_assoc($result);
  DEBUG($item);
  return $item;
}


function create_dyndns_account($handle, $password_http, $sshkey)
{
  $uid = (int) $_SESSION['userinfo']['uid'];

  if ($password_http == '' && $sshkey == '')
    system_failure('Sie müssen entweder einen SSH-Key oder ein Passwort zum Web-Update eingeben.');  

  $handle = maybe_null(mysql_real_escape_string(filter_input_username($handle)));
  $sshkey = maybe_null(mysql_real_escape_string(filter_input_general($sshkey)));

  $pwhash = 'NULL';
  if ($password_http)
    $pwhash = "'{SHA}".base64_encode(sha1($password_http, true))."'";

  db_query("INSERT INTO dns.dyndns (uid, handle, password, sshkey) VALUES ({$uid}, {$handle}, {$pwhash}, {$sshkey})");
  logger(LOG_INFO, "modules/dns/include/dnsinclude", "dyndns", "inserted account");
}


function edit_dyndns_account($id, $handle, $password_http, $sshkey)
{
  $id = (int) $id;
  $handle = maybe_null(mysql_real_escape_string(filter_input_username($handle)));
  $sshkey = maybe_null(mysql_real_escape_string(filter_input_general($sshkey)));

  $pwhash = 'NULL';
  if ($password_http)
  {
    if ($password_http == '************')
      $pwhash = 'password';
    else
      $pwhash = "'{SHA}".base64_encode(sha1($password_http, true))."'";
  }

  db_query("UPDATE dns.dyndns SET handle={$handle}, password={$pwhash}, sshkey={$sshkey} WHERE id={$id} LIMIT 1");
  logger(LOG_INFO, "modules/dns/include/dnsinclude", "dyndns", "edited account »{$id}«");
}


function delete_dyndns_account($id)
{
  $id = (int) $id;

  db_query("DELETE FROM dns.dyndns WHERE id={$id} LIMIT 1");
  logger(LOG_INFO, "modules/dns/include/dnsinclude", "dyndns", "deleted account »{$id}«");
}


function get_dyndns_records($id)
{
  $id = (int) $id;
  $result = db_query("SELECT hostname, domain, type, ttl, lastchange, id FROM dns.custom_records WHERE dyndns={$id}");
  $data = array();
  while ($entry = mysql_fetch_assoc($result)) {
    $dom = new Domain((int) $entry['domain']);
    $dom->ensure_customerdomain();
    $entry['fqdn'] = $entry['hostname'].'.'.$dom->fqdn;
    if (! $entry['hostname'])
      $entry['fqdn'] = $dom->fqdn;
    array_push($data, $entry);
  }
  DEBUG($data);
  return $data;
}

$valid_record_types = array('a', 'aaaa', 'mx', 'ns', 'spf', 'txt', 'cname', 'ptr', 'srv', 'raw');


function blank_dns_record($type)
{ 
  global $valid_record_types;
  if (!in_array(strtolower($type), $valid_record_types))
    system_failure('invalid type: '.$type);
  $rec = array('hostname' => NULL,
               'domain' => 0,
               'type' => strtolower($type),
               'ttl' => 3600,
               'ip' => NULL,
               'dyndns' => NULL,
               'data' => NULL,
               'spec' => NULL);
  if (strtolower($type) == 'mx')
  {
    $rec['data'] = config('default_mx');
    $rec['spec'] = '5';
  }
  return $rec;
}

function get_dns_record($id)
{
  $id = (int) $id;
  $result = db_query("SELECT hostname, domain, type, ip, dyndns, spec, data, ttl FROM dns.custom_records WHERE id={$id}");
  if (mysql_num_rows($result) != 1)
    system_failure('illegal ID');
  $data = mysql_fetch_assoc($result);
  $dom = new Domain( (int) $data['domain']);
  $dom->ensure_customerdomain();
  DEBUG($data);
  return $data;
}


function get_domain_records($dom)
{
  $dom = (int) $dom;
  $result = db_query("SELECT hostname, domain, type, ip, dyndns, spec, data, ttl, id FROM dns.custom_records WHERE domain={$dom}");
  $data = array();
  while ($entry = mysql_fetch_assoc($result)) {
    $dom = new Domain((int) $entry['domain']);
    $dom->ensure_customerdomain();
    $entry['fqdn'] = $entry['hostname'].'.'.$dom->fqdn;
    if (! $entry['hostname'])
      $entry['fqdn'] = $dom->fqdn;
    array_push($data, $entry);
  }
  DEBUG($data);
  return $data;
}

function get_domain_auto_records($domainname)
{
  $domainname = mysql_real_escape_string($domainname);
  #$result = db_query("SELECT hostname, domain, CONCAT_WS('.', hostname, domain) AS fqdn, type, ip, spec, data, ttl FROM dns.v_autogenerated_records WHERE domain='{$domainname}'");
  $result = db_query("SELECT hostname, domain, CONCAT_WS('.', hostname, domain) AS fqdn, type, ip, spec, data, ttl FROM dns.tmp_autorecords WHERE domain='{$domainname}'");
  $data = array();
  while ($entry = mysql_fetch_assoc($result)) {
    array_push($data, $entry);
  }
  DEBUG($data);
  return $data;
}


$implemented_record_types = array('a', 'aaaa', 'mx', 'spf', 'txt', 'cname', 'ptr', 'srv', 'ns');

function save_dns_record($id, $record)
{
  global $valid_record_types;
  global $implemented_record_types;
  $record['type'] = strtolower($record['type']);
  if (!in_array($record['type'], $valid_record_types))
    system_failure('invalid type: '.$record['type']);
  if (!in_array($record['type'], $implemented_record_types))
    system_failure('record type '.$record['type'].' not implemented at the moment.');
  $dom = new Domain( (int) $record['domain'] );
  $dom->ensure_customerdomain();
  if (! $dom->id)
    system_failure('invalid domain');
  verify_input_hostname($record['hostname'], true);
  if ($record['ttl'] &&  (int) $record['ttl'] < 1)
    system_failure('Fehler bei TTL');
  switch ($record['type']) 
  {
    case 'a':
      if ($record['dyndns'])
      {
        get_dyndns_account( $record['dyndns'] );
      	$record['ip'] = '';
      }
      else
      {
        verify_input_ipv4($record['ip']);
        $record['data'] = '';
        $record['spec'] = '';
      }
      break;
    case 'aaaa':
      $record['dyndns'] = '';
      verify_input_ipv6($record['ip']);
      $record['data'] = '';
      $record['spec'] = '';
      break;
    case 'mx':
      $record['dyndns'] = '';
      $record['spec'] = (int) $record['spec'];
      if ($record['spec'] < 1)
        systen_failure("invalid priority");
      verify_input_hostname($record['data']);
      if (! $record['data'] )
        system_failure('MX hostname missing');
      $record['ip'] = '';
      break;
    case 'cname':
    case 'ptr':
    case 'ns':
      $record['dyndns'] = '';
      $record['spec'] = '';
      $record['ip'] = '';
      verify_input_hostname($record['data']);
      if (! $record['data'] )
        system_failure('destination host missing');
      break;

    case 'spf':
    case 'txt':
      $record['dyndns'] = '';
      $record['spec'] = '';
      $record['ip'] = '';
      if (! $record['data'] )
        system_failure('text entry missing');
      break;

    case 'srv':
      system_failure('not implemented yet');
    default:
      system_failure('Not implemented');
  }
  $id = (int) $id;
  $record['hostname'] = maybe_null($record['hostname']);
  $record['ttl'] = ($record['ttl'] == 0 ? 'NULL' : (int) $record['ttl']);
  $record['ip'] = maybe_null($record['ip']);
  $record['data'] = maybe_null($record['data']);
  $record['spec'] = maybe_null($record['spec']);
  $record['dyndns'] = maybe_null($record['dyndns']);
  if ($id)
    db_query("UPDATE dns.custom_records SET hostname={$record['hostname']}, domain={$dom->id}, type='{$record['type']}', ttl={$record['ttl']}, ip={$record['ip']}, dyndns={$record['dyndns']}, data={$record['data']}, spec={$record['spec']} WHERE id={$id} LIMIT 1");
  else
    db_query("INSERT INTO dns.custom_records (hostname, domain, type, ttl, ip, dyndns, data, spec) VALUES ({$record['hostname']}, {$dom->id}, '{$record['type']}', {$record['ttl']}, {$record['ip']}, {$record['dyndns']}, {$record['data']}, {$record['spec']})");

}


function delete_dns_record($id)
{
  $id = (int) $id;
  // Diese Funktion prüft, ob der Eintrag einer eigenen Domain gehört
  $record = get_dns_record($id);
  db_query("DELETE FROM dns.custom_records WHERE id={$id} LIMIT 1");
}


function convert_from_autorecords($domainid)
{
  $dom = new Domain( (int) $domainid );
  $dom->ensure_customerdomain();
  $dom = $dom->id;

  db_query("INSERT IGNORE INTO dns.custom_records SELECT r.id, r.lastchange, type, d.id, hostname, ip, NULL AS dyndns, data, spec, ttl FROM dns.v_tmptable_allrecords AS r INNER JOIN dns.v_domains AS d ON (d.name=r.domain) WHERE d.id={$dom}");
  disable_autorecords($dom);
}


function enable_autorecords($domainid)
{
  $dom = new Domain( (int) $domainid );
  $dom->ensure_customerdomain();
  $dom = $dom->id;

  db_query("UPDATE kundendaten.domains SET autodns=1 WHERE id={$dom} LIMIT 1");
}

function disable_autorecords($domainid)
{
  $dom = new Domain( (int) $domainid );
  $dom->ensure_customerdomain();
  $dom = $dom->id;

  db_query("UPDATE kundendaten.domains SET autodns=0 WHERE id={$dom} LIMIT 1");
}


function sync_autorecords()
{
  db_query("CALL dns.sync_autorecords()");
}


?>

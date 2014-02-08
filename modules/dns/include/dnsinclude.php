<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2014 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/debug.php');
require_once('inc/base.php');
require_once('inc/security.php');
require_once('inc/error.php');

require_once('class/domain.php');


function get_dyndns_accounts() 
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT * FROM dns.dyndns WHERE uid=?", array($uid));
  $list = array();
  while ($item = $result->fetch()) {
    array_push($list, $item);
  }
  DEBUG($list);
  return $list;
}


function get_dyndns_account($id) 
{
  $args = array(":id" => (int) $id,
                ":uid" => (int) $_SESSION['userinfo']['uid']);
  $result = db_query("SELECT * FROM dns.dyndns WHERE id=:id AND uid=:uid", $args);
  if ($result->rowCount() != 1) {
    logger(LOG_WARNING, "modules/dns/include/dnsinclude", "dyndns", "account »{$id}« invalid for uid »{$uid}«.");
    system_failure("Account ungültig");
  }
  $item = $result->fetch();
  DEBUG($item);
  return $item;
}


function create_dyndns_account($handle, $password_http, $sshkey)
{
  $uid = (int) $_SESSION['userinfo']['uid'];

  if ($password_http == '' && $sshkey == '')
    system_failure('Sie müssen entweder einen SSH-Key oder ein Passwort zum Web-Update eingeben.');  

  $handle = filter_input_username($handle);
  $sshkey = filter_input_general($sshkey);

  $pwhash = NULL;
  if ($password_http)
    $pwhash = "{SHA}".base64_encode(sha1($password_http, true));

  db_query("INSERT INTO dns.dyndns (uid, handle, password, sshkey) VALUES ".
           "(:uid, :handle, :pwhash, :sshkey)",
           array(":uid" => $uid, ":handle" => $handle, ":pwhash" => $pwhash, ":sshkey" => $sshkey));
  logger(LOG_INFO, "modules/dns/include/dnsinclude", "dyndns", "inserted account");
}


function edit_dyndns_account($id, $handle, $password_http, $sshkey)
{
  $id = (int) $id;
  $handle = filter_input_username($handle);
  $sshkey = filter_input_general($sshkey);

  $pwhash = NULL;
  if ($password_http)
  {
    if ($password_http == '************')
      $pwhash = 'password';
    else
      $pwhash = "'{SHA}".base64_encode(sha1($password_http, true))."'";
  }
  
  $args = array(":handle" => $handle, ":pwhash" => $pwhash, ":sshkey" => $sshkey, ":id" => $id);
  db_query("UPDATE dns.dyndns SET handle=:handle, password=:pwhash, sshkey=:sshkey WHERE id=:id", $args);
  logger(LOG_INFO, "modules/dns/include/dnsinclude", "dyndns", "edited account »{$id}«");
}


function delete_dyndns_account($id)
{
  $id = (int) $id;

  db_query("DELETE FROM dns.dyndns WHERE id=?", array($id));
  logger(LOG_INFO, "modules/dns/include/dnsinclude", "dyndns", "deleted account »{$id}«");
}


function get_dyndns_records($id)
{
  $id = (int) $id;
  $result = db_query("SELECT hostname, domain, type, ttl, lastchange, id FROM dns.custom_records WHERE dyndns=?", array($id));
  $data = array();
  while ($entry = $result->fetch()) {
    $dom = new Domain((int) $entry['domain']);
    $dom->ensure_userdomain();
    $entry['fqdn'] = $entry['hostname'].'.'.$dom->fqdn;
    if (! $entry['hostname'])
      $entry['fqdn'] = $dom->fqdn;
    array_push($data, $entry);
  }
  DEBUG($data);
  return $data;
}

$valid_record_types = array('a', 'aaaa', 'mx', 'ns', 'spf', 'txt', 'cname', 'ptr', 'srv', 'raw', 'sshfp');


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
  $result = db_query("SELECT hostname, domain, type, ip, dyndns, spec, data, ttl FROM dns.custom_records WHERE id=?", array($id));
  if ($result->rowCount() != 1)
    system_failure('illegal ID');
  $data = $result->fetch();
  $dom = new Domain( (int) $data['domain']);
  $dom->ensure_userdomain();
  DEBUG($data);
  return $data;
}


function get_domain_records($dom)
{
  $dom = (int) $dom;
  $result = db_query("SELECT hostname, domain, type, ip, dyndns, spec, data, ttl, id FROM dns.custom_records WHERE domain=?", array($dom));
  $data = array();
  while ($entry = $result->fetch()) {
    $dom = new Domain((int) $entry['domain']);
    $dom->ensure_userdomain();
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
  $result = db_query("SELECT hostname, domain, CONCAT_WS('.', hostname, domain) AS fqdn, type, ip, spec, data, ttl FROM dns.tmp_autorecords WHERE domain=?", array($domainname));
  $data = array();
  while ($entry = $result->fetch()) {
    array_push($data, $entry);
  }
  DEBUG($data);
  return $data;
}


$implemented_record_types = array('a', 'aaaa', 'mx', 'spf', 'txt', 'cname', 'ptr', 'srv', 'ns', 'sshfp');

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
  $dom->ensure_userdomain();
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
      	$record['ip'] = NULL;
      }
      else
      {
        verify_input_ipv4($record['ip']);
        $record['data'] = NULL;
        $record['spec'] = NULL;
      }
      break;
    case 'aaaa':
      $record['dyndns'] = NULL;
      verify_input_ipv6($record['ip']);
      $record['data'] = NULL;
      $record['spec'] = NULL;
      break;
    case 'mx':
      $record['dyndns'] = NULL;
      $record['spec'] = (int) $record['spec'];
      if ($record['spec'] < 1)
        systen_failure("invalid priority");
      verify_input_hostname($record['data']);
      if (! $record['data'] )
        system_failure('MX hostname missing');
      $record['ip'] = NULL;
      break;
    case 'cname':
    case 'ptr':
    case 'ns':
      $record['dyndns'] = NULL;
      $record['spec'] = NULL;
      $record['ip'] = NULL;
      verify_input_hostname($record['data']);
      if (! $record['data'] )
        system_failure('destination host missing');
      break;

    case 'spf':
    case 'txt':
      $record['dyndns'] = NULL;
      $record['spec'] = NULL;
      $record['ip'] = NULL;
      if (! $record['data'] )
        system_failure('text entry missing');
      break;

    case 'sshfp':
      $record['dyndns'] = NULL;
      $record['spec'] = max( (int) $record['spec'], 1);
      $record['ip'] = NULL;
      if (! $record['data'] )
        system_failure('text entry missing');
      break;


    case 'srv':
      system_failure('not implemented yet');
    default:
      system_failure('Not implemented');
  }
  $id = (int) $id;
  $args = array(":domain" => $dom->id,
                ":hostname" => $record['hostname'],
                ":type" => $record['type'],
                ":ttl" => ($record['ttl'] == 0 ? NULL : (int) $record['ttl']),
                ":ip" => $record['ip'],
                ":dyndns" => $record['dyndns'],
                ":data" => $record['data'],
                ":spec" => $record['spec']);
  if ($id) {
    $args[":id"] = $id;
    db_query("UPDATE dns.custom_records SET hostname=:hostname, domain=:domain, type=:type, ttl=:ttl, ip=:ip, dyndns=:dyndns, data=:data, spec=:spec WHERE id=:id", $args);
  } else {
    db_query("INSERT INTO dns.custom_records (hostname, domain, type, ttl, ip, dyndns, data, spec) VALUES (:hostname, :domain, :type, :ttl, :ip, :dyndns, :data, :spec)", $args);
  }

}


function delete_dns_record($id)
{
  $id = (int) $id;
  // Diese Funktion prüft, ob der Eintrag einer eigenen Domain gehört
  $record = get_dns_record($id);
  db_query("DELETE FROM dns.custom_records WHERE id=?", array($id));
}


function convert_from_autorecords($domainid)
{
  $dom = new Domain( (int) $domainid );
  $dom->ensure_userdomain();
  $dom = $dom->id;

  db_query("INSERT IGNORE INTO dns.custom_records SELECT r.id, r.lastchange, type, d.id, hostname, ip, NULL AS dyndns, data, spec, ttl FROM dns.v_tmptable_allrecords AS r INNER JOIN dns.v_domains AS d ON (d.name=r.domain) WHERE d.id=?", array($dom));
  disable_autorecords($dom);
  db_query("UPDATE dns.dnsstatus SET status='outdated'");
  warning("Die automatischen Einträge werden in Kürze abgeschaltet, bitte haben Sie einen Moment Geduld.");
}


function enable_autorecords($domainid)
{
  $dom = new Domain( (int) $domainid );
  $dom->ensure_userdomain();
  $dom = $dom->id;

  db_query("UPDATE kundendaten.domains SET autodns=1 WHERE id=?", array($dom));
  warning("Die automatischen Einträge werden in Kürze aktiviert, bitte haben Sie einen Moment Geduld.");
}

function disable_autorecords($domainid)
{
  $dom = new Domain( (int) $domainid );
  $dom->ensure_userdomain();
  $dom = $dom->id;

  db_query("UPDATE kundendaten.domains SET autodns=0 WHERE id=?", array($dom));
}


function domain_is_maildomain($domain)
{
  $domain = (int) $domain;
  $result = db_query("SELECT mail FROM kundendaten.domains WHERE id=?", array($domain));
  $dom = $result->fetch();
  return ($dom['mail'] != 'none');
}


?>

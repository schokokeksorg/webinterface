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

require_once('inc/debug.php');


function mailman_subdomains($domain)
{
  if ( ! in_array('mailman', config('modules')))
  {
    return array();
  }
  $domain = (int) $domain;
  $result = db_query("SELECT id, hostname FROM mail.mailman_domains WHERE domain=?", array($domain));
  $ret = array();
  while ($line = $result->fetch())
  {
    $ret[] = $line;
  }
  return $ret;
}

function dns_in_use($domain)
{
  if ( ! in_array('dns', config('modules')))
    return false;
  $domain = (int) $domain;
  $result = db_query("SELECT id FROM dns.custom_records WHERE domain=?", array($domain));
  return ($result->rowCount() > 0);
}


function mail_in_use($domain)
{
  if ( ! in_array('email', config('modules')))
  {
    return false;
  }
  $domain = (int) $domain;
  $result = db_query("SELECT mail FROM kundendaten.domains WHERE id=?", array($domain));
  if ($result->rowCount() < 1)
    system_failure("Domain not found");
  $d = $result->fetch();
  if ($d['mail'] == 'none')
    return false; // manually disabled
  $result = db_query("SELECT id FROM mail.virtual_mail_domains WHERE domain=?", array($domain));
  if ($result->rowCount() < 1)
    return true; // .courier
  $result = db_query("SELECT acc.id FROM mail.vmail_accounts acc LEFT JOIN mail.virtual_mail_domains dom ON (acc.domain=dom.id) WHERE dom.domain=?", array($domain));
  return ($result->rowCount() > 0);
}

function web_in_use($domain)
{
  if ( ! in_array('vhosts', config('modules')))
    return false;

  $domain = (int) $domain;

  $result = db_query("SELECT id FROM kundendaten.domains WHERE id=? AND webserver=1", array($domain));
  if ($result->rowCount() < 1)
    return false;

  $result = db_query("SELECT id FROM vhosts.vhost WHERE domain=?", array($domain));
  $result2 = db_query("SELECT id FROM vhosts.alias WHERE domain=?", array($domain));
  return ($result->rowCount() > 0 || $result2->rowCount() > 0);
}

function domain_ownerchange($fqdn, $owner, $admin_c) 
{
    $cid = (int) $_SESSION['customerinfo']['customerno'];
    db_query("UPDATE kundendaten.domains SET owner=?, admin_c=? WHERE CONCAT_WS('.', domainname, tld)=? AND kunde=?", array($owner, $admin_c, $fqdn, $cid));
    if (update_possible($fqdn)) {
        require_once('domainapi.php');
        api_upload_domain($fqdn);
    }
}


function update_possible($domain) {
    $dom = new Domain((int) $domain);
    if ($dom->provider != 'terions' || $dom->billing=='external') {
        // Domain nicht über uns verwaltet
        return false;
    }
    $result = db_query("SELECT aenderung_eigentuemer, ruecksprache FROM misc.domainpreise WHERE tld=?", array($dom->tld));
    if ($result->rowCount() < 1) {
        // Endung nicht bei uns in der Liste erfasst
        return false;
    }
    $data = $result->fetch();
    if ($data['aenderung_eigentuemer'] != NULL || $data['ruecksprache'] == 'Y') {
        // Endung mit speziellen Eigenheiten
        return false;
    }
    return true;
}

function unset_mailserver_lock($dom) {
    $id = $dom->id;
    db_query("UPDATE kundendaten.domains SET secret=NULL, mailserver_lock=0 WHERE id=?", array($id));
}

function create_domain_secret($dom) {
    $id = $dom->id;
    $secret = md5(random_string(20));
    db_query("UPDATE kundendaten.domains SET secret=? WHERE id=?", array($secret, $id));
    $dom->secret = $secret;
    return $secret;
}


function get_auth_dns($domainname, $tld) {
  $domain=idn_to_ascii($domainname.".".$tld, 0, INTL_IDNA_VARIANT_UTS46);

  $resp = shell_exec('dig @a.root-servers.net. +noall +authority -t ns '.$tld.'.');
  $line = explode("\n", $resp, 2)[0];
  $NS = preg_replace("/^.*\\sIN\\s+NS\\s+(\\S+)$/", '\1', $line);

  $resp = shell_exec('dig @'.$NS.' -t ns '.$domain.'.');
  $lines = explode("\n", $resp);
  
  $NS = NULL;
  $NS_IP = NULL;
  $sec = NULL;
  foreach ($lines as $l) {
      if (preg_match("/;; AUTHORITY SECTION:.*/", $l)) {
          $sec = 'auth';
      } elseif (preg_match("/;; ADDITIONAL SECTION:.*/", $l)) {
          $sec = 'add';
      }
      if ($sec == 'auth' && preg_match("/^.*\\sIN\\s+NS\\s+\\S+$/", $l)) {
          $NS = preg_replace("/^.*\\sIN\\s+NS\\s+(\\S+)\\.$/", '\1', $l);
      }
      if ($sec == 'add' && $NS && preg_match("/^.*\\sIN\\s+A\\s+\\S+$/", $l)) {
          $NS_IP = preg_replace("/^.*\\sIN\\s+A\\s+(\\S+)$/", '\1', $l);
      }
  }
  return array("$NS" => $NS_IP);
}


function own_ns() {
    $auth = dns_get_record(config('masterdomain'), DNS_NS);
    $own_ns = array();
    foreach ($auth as $ns) {
        $own_ns[] = $ns['target'];
    }

    return $own_ns;  
}




function has_own_ns($domainname, $tld)
{
  $nsdata = get_auth_dns($domainname, $tld);
  $NS = NULL;
  foreach ($nsdata as $host => $ip) {
      $NS=$host;
  }
  if (in_array($NS, own_ns())) {
      DEBUG('Domain hat unsere DNS-Server!');
      return true;
  }
  return false;
}


function get_txt_record($hostname, $domainname, $tld) {
  $domain=idn_to_ascii($domainname.".".$tld, 0, INTL_IDNA_VARIANT_UTS46);
  $nsdata = get_auth_dns($domainname, $tld);
  $NS = NULL;
  foreach ($nsdata as $host => $ip) {
      $NS = $host;
      if ($ip) {
          $NS = $ip;
      }
  }
  DEBUG('dig @'.$NS.' +short -t txt '.$hostname.'.'.$domain.'.');
  $resp = shell_exec('dig @'.$NS.' +short -t txt '.$hostname.'.'.$domain.'.');
  $TXT = trim($resp, "\n \"");
  DEBUG($TXT);
  return $TXT;
}


function list_useraccounts()
{
  $customerno = (int) $_SESSION['customerinfo']['customerno'];
  $result = db_query("SELECT uid,username,name FROM system.useraccounts WHERE kunde=?", array($customerno));
  $ret = array();
  while ($item = $result->fetch())
  {
    $ret[] = $item;
  }
  DEBUG($ret);
  return $ret;
}


function change_user($domain, $uid) 
{
    $domain = new Domain($domain);
    $domain->ensure_customerdomain();
    $targetuser = NULL;
    $useraccounts = list_useraccounts();
    foreach ($useraccounts as $u) {
        if ($u['uid'] == $uid) {
            $targetuser = $u['uid'];
            break;
        }
    }
    if (! $targetuser) {
        system_failure("Ungültiger Useraccount!");
    }
    db_query("UPDATE kundendaten.domains SET useraccount=? WHERE id=?", array($targetuser, $domain->id));
}


function get_domain_offer($tld) 
{
  $tld = filter_input_hostname($tld);
  $cid = (int) $_SESSION['customerinfo']['customerno'];

  $data = array("tld" => $tld);

  $result = db_query("SELECT tld, gebuehr, `interval`, setup FROM misc.domainpreise_kunde WHERE kunde=:cid AND tld=:tld AND ruecksprache='N'", array(":cid" => $cid, ":tld" => $tld));
  if ($result->rowCount() != 1) {
    $result = db_query("SELECT tld, gebuehr, `interval`, setup FROM misc.domainpreise WHERE tld=:tld AND ruecksprache='N'", array(":tld" => $tld));
  }
  if ($result->rowCount() != 1) {
    return false;
  }
  $temp = $result->fetch();
  $data["gebuehr"] = $temp["gebuehr"];
  $data["interval"] = $temp["interval"];
  $data["setup"] = ($temp["setup"] ? $temp["setup"] : 0.0);
  
  return $data;
}

function set_domain_pretransfer($domain)
{
    $cid = (int) $_SESSION['customerinfo']['customerno'];
    $uid = (int) $_SESSION['userinfo']['uid'];
    $domain = (int) $domain;
    db_query("UPDATE kundendaten.domains SET status='pretransfer', dns=1 WHERE id=? AND kunde=?", 
            array($domain, $cid));
}



function set_domain_prereg($domain)
{
    $cid = (int) $_SESSION['customerinfo']['customerno'];
    $uid = (int) $_SESSION['userinfo']['uid'];
    $domain = (int) $domain;
    db_query("UPDATE kundendaten.domains SET status='prereg', dns=1 WHERE id=? AND kunde=?", 
            array($domain, $cid));
}


function insert_domain_external($domain, $dns = false, $mail = true)
{
    $cid = (int) $_SESSION['customerinfo']['customerno'];
    $uid = (int) $_SESSION['userinfo']['uid'];
    require_once("domainapi.php");
    $info = api_domain_available($domain);
    if (in_array($info['status'], array('nameContainsForbiddenCharacter', 'suffixDoesNotExist'))) {
        system_failure("Diese Domain scheint ungültig zu sein!");
    }
    $tld = $info['domainSuffix'];
    $domainname = str_replace(".$tld", "", $info['domainNameUnicode']);
    
    db_query("INSERT INTO kundendaten.domains (status, kunde, useraccount, domainname, tld, billing, provider, dns, mail, mailserver_lock) VALUES 
        ('external', ?, ?, ?, ?, 'external', 'other', 0, ?, 1)", array($cid, $uid, $domainname, $tld, ($mail ? 'auto' : 'none')));
    $id = db_insert_id();
    if ($dns) {
        db_query("UPDATE kundendaten.domains SET dns=1 WHERE id=?", array($id));
    }
    if ($mail) {
        $vmailserver = (int) $_SESSION['userinfo']['server'];
        db_query("INSERT INTO mail.virtual_mail_domains (domain, server) VALUES (?, ?)", array($id, $vmailserver));
    }
    return $id;
}

function delete_domain($id)
{
    $cid = (int) $_SESSION['customerinfo']['customerno'];
    db_query("DELETE FROM kundendaten.domains WHERE id=? AND kunde=?", array($id, $cid)); 
}



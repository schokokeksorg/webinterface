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
require_once('domainapi.php');


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
    api_upload_domain($fqdn);
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



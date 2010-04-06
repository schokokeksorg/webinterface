<?php

require_once('inc/base.php');
require_once('inc/debug.php');


function mailman_subdomains($domain)
{
  if ( ! in_array('mailman', config('modules')))
  {
    return array();
  }
  $domain = (int) $domain;
  $result = db_query("SELECT id, hostname FROM mail.mailman_domains WHERE domain={$domain}");
  $ret = array();
  while ($line = mysql_fetch_assoc($result))
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
  $result = db_query("SELECT id FROM dns.custom_records WHERE domain={$domain}");
  return (mysql_num_rows($result) > 0);
}


function mail_in_use($domain)
{
  if ( ! in_array('email', config('modules')))
  {
    return false;
  }
  $domain = (int) $domain;
  $result = db_query("SELECT mail FROM kundendaten.domains WHERE id={$domain}");
  if (mysql_num_rows($result) < 1)
    system_failure("Domain not found");
  $d = mysql_fetch_assoc($result);
  if ($d['mail'] == 'none')
    return false; // manually disabled
  $result = db_query("SELECT id FROM mail.virtual_mail_domains WHERE domain={$domain}");
  if (mysql_num_rows($result) < 1)
    return true; // .courier
  $result = db_query("SELECT acc.id FROM mail.vmail_accounts acc LEFT JOIN mail.virtual_mail_domains dom ON (acc.domain=dom.id) WHERE dom.domain={$domain}");
  return (mysql_num_rows($result) > 0);
}

function web_in_use($domain)
{
  if ( ! in_array('vhosts', config('modules')))
    return false;

  $domain = (int) $domain;

  $result = db_query("SELECT id FROM kundendaten.domains WHERE id={$domain} AND webserver=1");
  if (mysql_num_rows($result) < 1)
    return false;

  $result = db_query("SELECT id FROM vhosts.vhost WHERE domain={$domain}");
  $result2 = db_query("SELECT id FROM vhosts.alias WHERE domain={$domain}");
  return (mysql_num_rows($result) > 0 || mysql_num_rows($result2) > 0);
}



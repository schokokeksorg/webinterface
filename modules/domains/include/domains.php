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




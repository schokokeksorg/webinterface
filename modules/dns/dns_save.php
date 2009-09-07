<?php

require_once('inc/base.php');
require_once('inc/debug.php');
global $debugmode;
require_once('inc/security.php');

require_once('class/domain.php');

require_role(ROLE_CUSTOMER);

require_once('dnsinclude.php');

$section = 'dns_dns';

if ($_GET['type'] == 'autodns')
{
  if ($_GET['action'] == 'enable')
  {
    $dom = new Domain( (int) $_GET['dom'] );
    $dom->ensure_customerdomain();

    $sure = user_is_sure();
    if ($sure === NULL)
    {
      are_you_sure("type=autodns&action=enable&dom={$dom->id}", "Möchten Sie die automatischen DNS-records für {$dom->fqdn} einschalten?");
    }
    elseif ($sure === true)
    {
      enable_autorecords($dom->id);
      sync_autorecords();
      if (! $debugmode)
        header("Location: dns_domain?dom={$dom->id}");
    }
    elseif ($sure === false)
    {
      if (! $debugmode)
        header("Location: dns_domain?dom={$dom->id}");
    }
  }
  elseif ($_GET['action'] == 'disable')
  {
    $dom = new Domain( (int) $_GET['dom'] );
    $dom->ensure_customerdomain();

    $sure = user_is_sure();
    if ($sure === NULL)
    {
      are_you_sure("type=autodns&action=disable&dom={$dom->id}", "Möchten Sie die automatischen DNS-records für {$dom->fqdn} in manuelle Einträge umwandeln?");
    }
    elseif ($sure === true)
    {
      convert_from_autorecords($dom->id);
      sync_autorecords();
      if (! $debugmode)
        header("Location: dns_domain?dom={$dom->id}");
    }
    elseif ($sure === false)
    {
      if (! $debugmode)
        header("Location: dns_domain?dom={$dom->id}");
    }
  }
}



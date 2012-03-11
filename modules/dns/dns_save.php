<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2012 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/base.php');
require_once('inc/debug.php');
global $debugmode;
require_once('inc/security.php');

require_once('class/domain.php');

require_role(ROLE_SYSTEMUSER);

require_once('dnsinclude.php');

$section = 'dns_dns';

if ($_GET['type'] == 'autodns')
{
  if ($_GET['action'] == 'enable')
  {
    $dom = new Domain( (int) $_GET['dom'] );
    $dom->ensure_userdomain();

    $sure = user_is_sure();
    if ($sure === NULL)
    {
      are_you_sure("type=autodns&action=enable&dom={$dom->id}", "Möchten Sie die automatischen DNS-records für {$dom->fqdn} einschalten?");
    }
    elseif ($sure === true)
    {
      enable_autorecords($dom->id);
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
    $dom->ensure_userdomain();

    $sure = user_is_sure();
    if ($sure === NULL)
    {
      are_you_sure("type=autodns&action=disable&dom={$dom->id}", "Möchten Sie die automatischen DNS-records für {$dom->fqdn} in manuelle Einträge umwandeln?");
    }
    elseif ($sure === true)
    {
      convert_from_autorecords($dom->id);
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



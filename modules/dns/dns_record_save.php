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

require_role(ROLE_SYSTEMUSER);

require_once('dnsinclude.php');

$section = 'dns_dns';


$record = NULL;

$id = NULL;
if ($_REQUEST['id'] == 'new')
{
  $record = blank_dns_record($_REQUEST['type']);
}
else
{
  $id = (int) $_REQUEST['id'];
  $record = get_dns_record($id);
}


if (isset($_GET['action']) && ($_GET['action'] == 'delete')) {
  $sure = user_is_sure();
  if ($sure === NULL)
  {
    $domain = new Domain((int) $record['domain']);
    $fqdn = $domain->fqdn;
    if ($record['hostname'])
    {
      $fqdn = $record['hostname'].'.'.$fqdn;
    }
    are_you_sure("action=delete&id={$id}", "Möchten Sie den ".strtoupper($record['type'])."-Record für ".$fqdn." wirklich löschen?");
  }
  elseif ($sure === true)
  {
    delete_dns_record($id);
    if (! $debugmode)
      header("Location: dns_domain?dom=".$record['domain']);
  }
  elseif ($sure === false)
  {
    if (! $debugmode)
      header("Location: dns_domain?dom=".$record['domain']);
  }
}
else
{
  // Sicherheitsprüfungen passieren im Backend
  
  $record['hostname'] = $_REQUEST['hostname'];
  $record['domain'] = (int) $_REQUEST['domain'];
  $record['ip'] = (isset($_REQUEST['ip']) ? $_REQUEST['ip'] : '');
  $record['data'] = $_REQUEST['data'];
  $record['dyndns'] = (isset($_REQUEST['dyndns']) ? (int) $_REQUEST['dyndns'] : '');
  $record['spec'] = (isset($_REQUEST['spec']) ? (int) $_REQUEST['spec'] : '');
  $record['ttl'] = (int) $_REQUEST['ttl'];
  
  save_dns_record($id, $record);

  if (!$debugmode)
    header('Location: dns_domain?dom='.$record['domain']);
}




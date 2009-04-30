<?php

require_once('inc/base.php');
require_once('inc/debug.php');
global $debugmode;
require_once('inc/security.php');

require_role(ROLE_CUSTOMER);

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


if ($_GET['action'] == 'delete') {
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
  $record['ip'] = $_REQUEST['ip'];
  $record['data'] = $_REQUEST['data'];
  $record['dyndns'] = (int) $_REQUEST['dyndns'];
  $record['spec'] = (int) $_REQUEST['spec'];
  $record['ttl'] = (int) $_REQUEST['ttl'];
  
  save_dns_record($id, $record);

  if (!$debugmode)
    header('Location: dns_domain?dom='.$record['domain']);
}




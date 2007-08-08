<?php

require_once("inc/base.php");
require_once("inc/error.php");
require_once("inc/security.php");

require_once('class/domain.php');

function list_vhosts()
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT id,fqdn,docroot,docroot_is_default,php,options FROM vhosts.v_vhost WHERE uid={$uid} ORDER BY domain,hostname");
  $ret = array();
  while ($item = mysql_fetch_assoc($result))
    array_push($ret, $item);
  return $ret;
}


function empty_vhost()
{
  $vhost['hostname'] = '';
  
  $domainlist = get_domain_list($_SESSION['customerinfo']['customerno'],
                                $_SESSION['userinfo']['uid']);
  $dom = $domainlist[0];

  $vhost['domain_id'] = $dom->id;
  $vhost['domain'] = $dom->fqdn;
  
  $vhost['homedir'] = $_SESSION['userinfo']['homedir'];
  $vhost['docroot'] = NULL;
  $vhost['php'] = 'mod_php';
  $vhost['logtype'] = NULL;
    
  $vhost['options'] = '';
  return $vhost;
}


function domainselect($selected = NULL, $selectattribute = '')
{
  global $domainlist;
  if ($domainlist == NULL)
    $domainlist = get_domain_list($_SESSION['customerinfo']['customerno'],
                                  $_SESSION['userinfo']['uid']);
  $selected = (int) $selected;

  $ret = '<select id="domain" name="domain" size="1" '.$selectattribute.' >';
  foreach ($domainlist as $dom)
  {
    $s = '';
    if ($selected == $dom->id)
      $s = ' selected="selected" ';
    $ret .= "<option value=\"{$dom->id}\"{$s}>{$dom->fqdn}</option>\n";
  }
  $ret .= '</select>';
  return $ret;
}



function get_vhost_details($id)
{
  $id = (int) $id;
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT * FROM vhosts.v_vhost WHERE uid={$uid} AND id={$id}");
  if (mysql_num_rows($result) != 1)
    system_failure('Interner Fehler beim Auslesen der Daten');

  return mysql_fetch_assoc($result);
}



function get_aliases($vhost)
{
  $vhost = (int) $vhost;
  $result = db_query("SELECT id,fqdn,options FROM vhosts.v_alias WHERE vhost={$vhost}");
  $ret = array();
  while ($item = mysql_fetch_assoc($result))
    array_push($ret, $item);
  return $ret;
}

function delete_vhost($id)
{
  $id = (int) $id;
  if ($id == 0)
    system_failure("id == 0");
  $vhost = get_vhost_details($id);
  logger('modules/vhosts/include/vhosts.php', 'vhosts', 'Removing vhost #'.$id.' ('.$vhost['hostname'].'.'.$vhost['domain'].')');
  db_query("DELETE FROM vhosts.vhost WHERE id={$vhost['id']} LIMIT 1");
}

function save_vhost($vhost)
{
  if (! is_array($vhost))
    system_failure('$vhost kein array!');
  $id = (int) $vhost['id'];
  $hostname = maybe_null($vhost['hostname']);
  $domain = (int) $vhost['domainid'];
  if ($domain == 0)
    system_failure("Domain == 0");
  $docroot = maybe_null($vhost['docroot']);
  $php = maybe_null($vhost['php']);
  $logtype = maybe_null($vhost['logtype']);
  $options = mysql_real_escape_string( $vhost['options'] );

  if ($id != 0) {
    logger('modules/vhosts/include/vhosts.php', 'vhosts', 'Updating vhost #'.$id.' ('.$vhost['hostname'].'.'.$vhost['domain'].')');
    db_query("UPDATE vhosts.vhost SET hostname={$hostname}, domain={$domain}, docroot={$docroot}, php={$php}, logtype={$logtype}, options='{$options}' WHERE id={$id} LIMIT 1");
  }
  else {
    logger('modules/vhosts/include/vhosts.php', 'vhosts', 'Creating vhost '.$vhost['hostname'].'.'.$vhost['domain'].'');
    db_query("INSERT INTO vhosts.vhost (user, hostname, domain, docroot, php, logtype, options) VALUES ({$_SESSION['userinfo']['uid']}, {$hostname}, {$domain}, {$docroot}, {$php}, {$logtype}, '{$options}')");
  }
}




?>

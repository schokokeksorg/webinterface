<?php

require_once("inc/base.php");
require_once("inc/error.php");
require_once("inc/security.php");

require_once('class/domain.php');


function list_vhosts()
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT vh.id,fqdn,docroot,docroot_is_default,php,vh.options,logtype,errorlog,IF(dav.id IS NULL OR dav.type='svn', 0, 1) AS is_dav,IF(dav.id IS NULL OR dav.type='dav', 0, 1) AS is_svn, IF(webapps.id IS NULL, 0, 1) AS is_webapp FROM vhosts.v_vhost AS vh LEFT JOIN vhosts.dav ON (dav.vhost=vh.id) LEFT JOIN vhosts.webapps ON (webapps.vhost = vh.id) WHERE uid={$uid} ORDER BY domain,hostname");
  $ret = array();
  while ($item = mysql_fetch_assoc($result))
    array_push($ret, $item);
  return $ret;
}


function empty_vhost()
{
  $vhost['hostname'] = '';
  
  $vhost['domain_id'] = -1;
  $vhost['domain'] = $_SESSION['userinfo']['username'].'.schokokeks.org';
  
  $vhost['homedir'] = $_SESSION['userinfo']['homedir'];
  $vhost['docroot'] = NULL;
  $vhost['php'] = 'mod_php';
  $vhost['ssl'] = NULL;
  $vhost['logtype'] = NULL;
  $vhost['is_dav'] = 0;
  $vhost['is_svn'] = 0;
  $vhost['is_webapp'] = 0;
  $vhsot['webapp_id'] = NULL;
    
  $vhost['options'] = '';
  return $vhost;
}


function empty_alias()
{
  $alias['hostname'] = '';
  
  $alias['domain_id'] = -1;
  $alias['domain'] = $_SESSION['userinfo']['username'].'.schokokeks.org';
  
  $alias['options'] = '';
  return $alias;
}


function domainselect($selected = NULL, $selectattribute = '')
{
  global $domainlist;
  if ($domainlist == NULL)
    $domainlist = get_domain_list($_SESSION['customerinfo']['customerno'],
                                  $_SESSION['userinfo']['uid']);
  $selected = (int) $selected;

  $ret = '<select id="domain" name="domain" size="1" '.$selectattribute.' >';
  $ret .= ' <option value="-1">'.$_SESSION['userinfo']['username'].'.schokokeks.org</option>';
  $ret .= ' <option value="" disabled="disabled">--------------------------------</option>';
  foreach ($domainlist as $dom)
  {
    $s = ($selected == $dom->id) ? ' selected="selected" ': '';
    $ret .= "<option value=\"{$dom->id}\"{$s}>{$dom->fqdn}</option>\n";
  }
  $ret .= '</select>';
  return $ret;
}



function get_vhost_details($id)
{
  $id = (int) $id;
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT vh.*,IF(dav.id IS NULL OR dav.type='svn', 0, 1) AS is_dav,IF(dav.id IS NULL OR dav.type='dav', 0, 1) AS is_svn, IF(webapps.id IS NULL, 0, 1) AS is_webapp FROM vhosts.v_vhost AS vh LEFT JOIN vhosts.dav ON (dav.vhost=vh.id) LEFT JOIN vhosts.webapps ON (webapps.vhost = vh.id) WHERE uid={$uid} AND vh.id={$id}");
  if (mysql_num_rows($result) != 1)
    system_failure('Interner Fehler beim Auslesen der Daten');

  return mysql_fetch_assoc($result);
}


function get_aliases($vhost)
{
  $result = db_query("SELECT id,fqdn,options FROM vhosts.v_alias WHERE vhost={$vhost}");
  $ret = array();
  while ($item = mysql_fetch_assoc($result)) {
    array_push($ret, $item);
  }
  return $ret;
}



function get_all_aliases($vhost)
{
  $vhost = get_vhost_details( (int) $vhost );
  $aliases = get_aliases($vhost['id']);
  $ret = array();
  if (strstr($vhost['options'], 'aliaswww')) {
    array_push($ret, array('id' => 'www', 'fqdn' => 'www.'.$vhost['fqdn'], 'options' => (strstr($vhost['options'], 'forwardwww') ? 'forward' : '')));
  }
  foreach ($aliases as $item) {
    array_push($ret, $item);
    if (strstr($item['options'], 'aliaswww')) {
      array_push($ret, array('id' => 'www_'.$item['id'], 'fqdn' => 'www.'.$item['fqdn'], 'options' => (strstr($item['options'], 'forward') ? 'forward' : '')));
    }
  }
  return $ret;
}


function list_available_webapps()
{
  $result = db_query("SELECT id,displayname FROM vhosts.global_webapps");
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



function make_svn_vhost($id) 
{
  $id = (int) $id;
  if ($id == 0)
    system_failure("id == 0");
  logger('modules/vhosts/include/vhosts.php', 'vhosts', 'Converting vhost #'.$id.' to SVN');
  db_query("REPLACE INTO vhosts.dav (vhost, type) VALUES ({$id}, 'svn')");
  db_query("DELETE FROM vhosts.webapps WHERE vhost={$id}");
}

function make_dav_vhost($id) 
{
  $id = (int) $id;
  if ($id == 0)
    system_failure("id == 0");
  logger('modules/vhosts/include/vhosts.php', 'vhosts', 'Converting vhost #'.$id.' to WebDAV');
  db_query("REPLACE INTO vhosts.dav (vhost, type) VALUES ({$id}, 'dav')");
  db_query("DELETE FROM vhosts.webapps WHERE vhost={$id}");
}

function make_regular_vhost($id)
{
  $id = (int) $id;
  if ($id == 0)
    system_failure("id == 0");
  logger('modules/vhosts/include/vhosts.php', 'vhosts', 'Converting vhost #'.$id.' to regular');
  db_query("DELETE FROM vhosts.dav WHERE vhost={$id}");
  db_query("DELETE FROM vhosts.webapps WHERE vhost={$id}");
}


function make_webapp_vhost($id, $webapp) 
{
  $id = (int) $id;
  $webapp = (int) $webapp;
  if ($id == 0)
    system_failure("id == 0");
  $result = db_query("SELECT displayname FROM vhosts.global_webapps WHERE id={$webapp};");
  if (mysql_num_rows($result) == 0)
    system_failure("webapp-id invalid");
  $webapp_name = mysql_fetch_object($result)->displayname;
  logger('modules/vhosts/include/vhosts.php', 'vhosts', 'Setting up webapp '.$webapp_name.' on vhost #'.$id);
  db_query("REPLACE INTO vhosts.webapps (vhost, webapp) VALUES ({$id}, {$webapp})");
  mail('webapps-setup@schokokeks.org', 'setup', 'setup');
}


function save_vhost($vhost)
{
  if (! is_array($vhost))
    system_failure('$vhost kein array!');
  $id = (int) $vhost['id'];
  $hostname = maybe_null($vhost['hostname']);
  $domain = (int) $vhost['domainid'];
  if ($domain == 0)
    system_failure('$domain == 0');
  if ($vhost['domainid'] == -1)
    $domain = 'NULL';
  $docroot = maybe_null($vhost['docroot']);
  $php = maybe_null($vhost['php']);
  $ssl = maybe_null($vhost['ssl']);
  $logtype = maybe_null($vhost['logtype']);
  $errorlog = (int) $vhost['errorlog'];
  $options = mysql_real_escape_string( $vhost['options'] );

  if ($id != 0) {
    logger('modules/vhosts/include/vhosts.php', 'vhosts', 'Updating vhost #'.$id.' ('.$vhost['hostname'].'.'.$vhost['domain'].')');
    db_query("UPDATE vhosts.vhost SET hostname={$hostname}, domain={$domain}, docroot={$docroot}, php={$php}, `ssl`={$ssl}, logtype={$logtype}, errorlog={$errorlog}, options='{$options}' WHERE id={$id} LIMIT 1");
  }
  else {
    logger('modules/vhosts/include/vhosts.php', 'vhosts', 'Creating vhost '.$vhost['hostname'].'.'.$vhost['domain'].'');
    $result = db_query("INSERT INTO vhosts.vhost (user, hostname, domain, docroot, php, `ssl`, logtype, errorlog, options) VALUES ({$_SESSION['userinfo']['uid']}, {$hostname}, {$domain}, {$docroot}, {$php}, {$ssl}, {$logtype}, {$errorlog}, '{$options}')");
    $id = mysql_insert_id();
  }
  $oldvhost = get_vhost_details($id);
  /*
    these vars may be 0 or 1.
    So newval > oldval means that it has been switched on yet.
  */
  if ($vhost['is_dav'] > $oldvhost['is_dav'])
      make_dav_vhost($id);
  elseif ($vhost['is_svn'] > $oldvhost['is_svn'])
      make_svn_vhost($id);
  elseif ($vhost['is_webapp'] > $oldvhost['is_webapp'])
      make_webapp_vhost($id, $vhost['webapp_id']);
  elseif ($vhost['is_dav'] == 0 && $vhost['is_svn'] == 0 && $vhost['is_webapp'] == 0)
      make_regular_vhost($id);
}


function get_alias_details($id)
{
  $id = (int) $id;
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT * FROM vhosts.v_alias WHERE id={$id}");
  
  if (mysql_num_rows($result) != 1)
    system_failure('Interner Fehler beim Auslesen der Alias-Daten');
  
  $alias = mysql_fetch_assoc($result);
  
  if ($alias['domain_id'] == NULL) {
    $alias['domain_id'] = -1;
  }

  /* Das bewirkt, dass nur die eigenen Aliase gesehen werden können */
  get_vhost_details( (int) $alias['vhost'] );

  return $alias;
}


function delete_alias($id)
{
  $id = (int) $id;
  $alias = get_alias_details($id);

  logger('modules/vhosts/include/vhosts.php', 'aliases', 'Removing alias #'.$id.' ('.$alias['hostname'].'.'.$alias['domain'].')');
  db_query("DELETE FROM vhosts.alias WHERE id={$id}");
}

function save_alias($alias)
{
  if (! is_array($alias))
    system_failure('$alias kein array!');
  $id = (int) $alias['id'];
  $hostname = maybe_null($alias['hostname']);
  $domain = (int) $alias['domainid'];
  if ($domain == 0)
    system_failure('$domain == 0');
  if ($alias['domainid'] == -1)
    $domain = 'NULL';
  $vhost = get_vhost_details( (int) $alias['vhost']);
  $options = mysql_real_escape_string( $alias['options'] );
  if ($id == 0) {
    logger('modules/vhosts/include/vhosts.php', 'aliases', 'Creating alias '.$alias['hostname'].'.'.$alias['domain'].' for VHost '.$vhost['id']);
    db_query("INSERT INTO vhosts.alias (hostname, domain, vhost, options) VALUES ({$hostname}, {$domain}, {$vhost['id']}, '{$options}')");
  }
  else {
    logger('modules/vhosts/include/vhosts.php', 'aliases', 'Updating alias #'.$id.' ('.$alias['hostname'].'.'.$alias['domain'].')');
    db_query("UPDATE vhosts.alias SET hostname={$hostname}, domain={$domain}, options='{$options}' WHERE id={$id} LIMIT 1");
  }
}



?>

<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2014 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once("inc/base.php");
require_once("inc/error.php");
require_once("inc/security.php");

require_once('class/domain.php');

require_once("certs.php");


function traffic_month($vhost_id)
{
  $vhost_id = (int) $vhost_id;
  $result = db_query("SELECT sum(mb_in+mb_out) as mb FROM vhosts.traffic where date > CURDATE() - INTERVAL 1 MONTH AND vhost_id = ?", array($vhost_id));
  $data = $result->fetch();
  return $data['mb'];
}

function autoipv6_address($vhost_id, $mode = 1)
{
  $result = db_query("SELECT uid, v6_prefix FROM vhosts.v_vhost LEFT JOIN system.servers ON (servers.hostname = server) WHERE v_vhost.id=?", array($vhost_id));
  $data = $result->fetch();
  if (!$data['v6_prefix'])
  {
    warning("IPv6-Adresse nicht verfügbar, Server unterstützt kein IPv6");
    return "";
  }
  list($prefix, $null) = explode('/', $data['v6_prefix']);
  $vh = ':1';
  if ($mode == 2) {
    $vh = implode(':', str_split(sprintf("%08x", $vhost_id), 4));
  }
  $ipv6 = $prefix . sprintf("%04s", $data['uid']) . ':' . $vh;
  return $ipv6;
}


function list_vhosts()
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT vh.id,fqdn,domain,docroot,docroot_is_default,php,cgi,vh.certid AS cert, vh.ssl, vh.options,logtype,errorlog,IF(dav.id IS NULL OR dav.type='svn', 0, 1) AS is_dav,IF(dav.id IS NULL OR dav.type='dav', 0, 1) AS is_svn, IF(webapps.id IS NULL, 0, 1) AS is_webapp, stats FROM vhosts.v_vhost AS vh LEFT JOIN vhosts.dav ON (dav.vhost=vh.id) LEFT JOIN vhosts.webapps ON (webapps.vhost = vh.id) WHERE uid=? ORDER BY domain,hostname", array($uid));
  $ret = array();
  while ($item = $result->fetch())
    array_push($ret, $item);
  return $ret;
}

function ipv6_possible($server)
{
  $args = array(":server" => $server);
  $result = db_query("SELECT v6_prefix FROM system.servers WHERE id=:server OR hostname=:server", $args);
  $line = $result->fetch();
  DEBUG("Server {$server} is v6-capable: ". ($line['v6_prefix'] != NULL));
  return ($line['v6_prefix'] != NULL);
}

function empty_vhost()
{
  $vhost['id'] = NULL;
  $vhost['hostname'] = NULL;
  
  $vhost['domain_id'] = -1;
  $vhost['domain'] = $_SESSION['userinfo']['username'].'.'.config('masterdomain');
  
  $vhost['homedir'] = $_SESSION['userinfo']['homedir'];
  $vhost['docroot'] = NULL;
  $vhost['php'] = 'php55';
  $vhost['cgi'] = 1;
  $vhost['ssl'] = NULL;
  $vhost['hsts'] = -1;
  $vhost['suexec_user'] = NULL;
  $vhost['server'] = NULL;
  $vhost['logtype'] = NULL;
  $vhost['errorlog'] = 0;
  $vhost['is_dav'] = 0;
  $vhost['is_svn'] = 0;
  $vhost['is_webapp'] = 0;
  $vhost['webapp_id'] = NULL;
  
  $vhost['cert'] = NULL;
  $vhost['certid'] = NULL;
  $vhost['ipv4'] = NULL;
  $vhost['autoipv6'] = 2; // 1 => Eine IP pro User, 2 => Eine IP pro VHost

  $vhost['options'] = '';
  $vhost['stats'] = NULL;
  return $vhost;
}


function empty_alias()
{
  $alias['hostname'] = NULL;
  
  $alias['domain_id'] = -1;
  $alias['domain'] = $_SESSION['userinfo']['username'].'.'.config('masterdomain');
  
  $alias['options'] = NULL;
  return $alias;
}


function domainselect($selected = NULL, $selectattribute = '')
{
  global $domainlist, $config;
  if ($domainlist == NULL)
    $domainlist = get_domain_list($_SESSION['customerinfo']['customerno'],
                                  $_SESSION['userinfo']['uid']);
  $selected = (int) $selected;

  $ret = '<select id="domain" name="domain" size="1" '.$selectattribute.' >';
  $ret .= ' <option value="-1">'.$_SESSION['userinfo']['username'].'.'.config('masterdomain').'</option>';
  $ret .= ' <option value="" disabled="disabled">--------------------------------</option>';
  $found = false;
  foreach ($domainlist as $dom)
  {
    $s = '';
    if ($selected == $dom->id) {
      $s = ' selected="selected" ';
      $found = true;
    }
    $ret .= "<option value=\"{$dom->id}\"{$s}>{$dom->fqdn}</option>\n";
  }
  $ret .= '</select>';
  if ($selected > 0 and ! $found) {
    system_failure("Hier wird eine Domain benutzt, die nicht zu diesem Benutzeraccount gehört. Bearbeiten würde Daten zerstören!");
  }
  return $ret;
}



function get_vhost_details($id)
{
  $id = (int) $id;
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT vh.*,IF(dav.id IS NULL OR dav.type='svn', 0, 1) AS is_dav,IF(dav.id IS NULL OR dav.type='dav', 0, 1) AS is_svn, IF(webapps.id IS NULL, 0, 1) AS is_webapp FROM vhosts.v_vhost AS vh LEFT JOIN vhosts.dav ON (dav.vhost=vh.id) LEFT JOIN vhosts.webapps ON (webapps.vhost = vh.id) WHERE uid=:uid AND vh.id=:id", array(":uid" => $uid, ":id" => $id));
  if ($result->rowCount() != 1)
    system_failure('Interner Fehler beim Auslesen der Daten');

  $ret = $result->fetch();

  if ($ret['hsts'] === NULL) {
    DEBUG('HSTS: '.$ret['hsts']);
    $ret['hsts'] = -1;
  }
  $ret['server'] = $ret['server_id'];
  DEBUG($ret);
  return $ret;
}


function get_aliases($vhost)
{
  $result = db_query("SELECT id,fqdn,options FROM vhosts.v_alias WHERE vhost=?", array($vhost));
  $ret = array();
  while ($item = $result->fetch()) {
    array_push($ret, $item);
  }
  return $ret;
}



function get_all_aliases($vhost)
{
  //$vhost = get_vhost_details( (int) $vhost );
  $aliases = get_aliases($vhost['id']);
  $ret = array();
  if (strstr($vhost['options'], 'aliaswww')) {
    array_push($ret, array('id' => 'www', 'fqdn' => 'www.'.$vhost['fqdn'], 'options' => (strstr($vhost['options'], 'forwardwww') ? 'forward' : NULL)));
  }
  foreach ($aliases as $item) {
    array_push($ret, $item);
    if (strstr($item['options'], 'aliaswww')) {
      array_push($ret, array('id' => 'www_'.$item['id'], 'fqdn' => 'www.'.$item['fqdn'], 'options' => (strstr($item['options'], 'forward') ? 'forward' : NULL)));
    }
  }
  return $ret;
}


function list_available_webapps()
{
  $result = db_query("SELECT id,displayname FROM vhosts.global_webapps");
  $ret = array();
  while ($item = $result->fetch())
    array_push($ret, $item);
  return $ret;
}


function delete_vhost($id)
{
  $id = (int) $id;
  if ($id == 0)
    system_failure("id == 0");
  $vhost = get_vhost_details($id);
  logger(LOG_INFO, 'modules/vhosts/include/vhosts', 'vhosts', 'Removing vhost #'.$id.' ('.$vhost['hostname'].'.'.$vhost['domain'].')');
  db_query("DELETE FROM vhosts.vhost WHERE id=?", array($vhost['id']));
}



function make_svn_vhost($id) 
{
  $id = (int) $id;
  if ($id == 0)
    system_failure("id == 0");
  logger(LOG_INFO, 'modules/vhosts/include/vhosts', 'vhosts', 'Converting vhost #'.$id.' to SVN');
  db_query("REPLACE INTO vhosts.dav (vhost, type) VALUES (?, 'svn')", array($id));
  db_query("DELETE FROM vhosts.webapps WHERE vhost=?", array($id));
}

function make_dav_vhost($id) 
{
  $id = (int) $id;
  if ($id == 0)
    system_failure("id == 0");
  logger(LOG_INFO, 'modules/vhosts/include/vhosts', 'vhosts', 'Converting vhost #'.$id.' to WebDAV');
  db_query("REPLACE INTO vhosts.dav (vhost, type, options) VALUES (?, 'dav', 'nouserfile')", array($id));
  db_query("DELETE FROM vhosts.webapps WHERE vhost=?", array($id));
}

function make_regular_vhost($id)
{
  $id = (int) $id;
  if ($id == 0)
    system_failure("id == 0");
  logger(LOG_INFO, 'modules/vhosts/include/vhosts', 'vhosts', 'Converting vhost #'.$id.' to regular');
  db_query("DELETE FROM vhosts.dav WHERE vhost=?", array($id));
  db_query("DELETE FROM vhosts.webapps WHERE vhost=?", array($id));
}


function make_webapp_vhost($id, $webapp) 
{
  $id = (int) $id;
  $webapp = (int) $webapp;
  if ($id == 0)
    system_failure("id == 0");
  $result = db_query("SELECT displayname FROM vhosts.global_webapps WHERE id=?", array($webapp));
  if ($result->rowCount() == 0)
    system_failure("webapp-id invalid");
  $webapp_name = $result->fetch(PDO::FETCH_OBJ)->displayname;
  logger(LOG_INFO, 'modules/vhosts/include/vhosts', 'vhosts', 'Setting up webapp '.$webapp_name.' on vhost #'.$id);
  db_query("REPLACE INTO vhosts.webapps (vhost, webapp) VALUES (?, ?)", array($id, $webapp));
  mail('webapps-setup@schokokeks.org', 'setup', 'setup');
}


function check_hostname_collision($hostname, $domain) 
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  # Neuer vhost => Prüfe Duplikat
  $args = array(":hostname" => $hostname, ":domain" => $domain);
  $hostnamecheck = "hostname=:hostname";
  if (! $hostname) {
    $hostnamecheck = "hostname IS NULL";
    unset($args[":hostname"]);
  }
  $domaincheck = "domain=:domain";
  if ($domain == -1) {
    $args[":uid"] = $uid;
    unset($args[":domain"]);
    $domaincheck = "domain IS NULL AND user=:uid";
  }
  $result = db_query("SELECT id FROM vhosts.vhost WHERE {$hostnamecheck} AND {$domaincheck}", $args);
  if ($result->rowCount() > 0) {
    system_failure('Eine Konfiguration mit diesem Namen gibt es bereits.');
  }
  if ($domain == -1) {
    return ;
  }
  $result = db_query("SELECT id, vhost FROM vhosts.alias WHERE {$hostnamecheck} AND {$domaincheck}", $args);
  if ($result->rowCount() > 0) {
    $data = $result->fetch();
    $vh = get_vhost_details($data['vhost']);
    system_failure('Dieser Hostname ist bereits als Alias für »'.$vh['fqdn'].'« eingerichtet');
  }
}

function save_vhost($vhost)
{
  if (! is_array($vhost))
    system_failure('$vhost kein array!');
  $id = (int) $vhost['id'];
  $hostname = $vhost['hostname'];
  $domain = (int) $vhost['domain_id'];
  if ($domain == 0)
    system_failure('$domain == 0');
  if ($vhost['domain_id'] == -1)
    $domain = NULL;
  if ($id == 0) {
    check_hostname_collision($vhost['hostname'], $vhost['domain_id']);
  }
  $hsts = (int) $vhost['hsts'];
  if ($hsts < 0) {
    $hsts = NULL;
  }
  $suexec_user = NULL;

  $available_suexec = available_suexec_users();
  foreach ($available_suexec AS $u)
    if ($u['uid'] == $vhost['suexec_user'])
      $suexec_user = $u['uid'];

  $server = NULL;
  $available_servers = additional_servers();
  if (in_array($vhost['server'], $available_servers)) {
    $server = (int) $vhost['server'];
  }
  if ($server == my_server_id()) {
    $server = NULL;
  }

  if ($vhost['is_svn']) {
    if (! $vhost['options']) {
      $vhost['options']='nodocroot';
    } else {
      $vhost['options']+=",nodocroot";
    }
  }

  $cert = NULL;
  $certs = user_certs();
  foreach ($certs as $c)
    if ($c['id'] == $vhost['cert'])
      $cert = $c['id'];

  $ipv4 = NULL;
  $ipv4_avail = user_ipaddrs();
  if (in_array($vhost['ipv4'], $ipv4_avail))
  {
    $ipv4 = $vhost['ipv4'];
  }

  $autoipv6 = 1;
  if ($vhost['autoipv6'] == 0 ||  $vhost['autoipv6'] == 2) {
    $autoipv6 = $vhost['autoipv6'];
  }

  $args = array(":hostname" => ($hostname ? $hostname : NULL),
                ":domain" => $domain,
                ":docroot" => $vhost['docroot'],
                ":php" => $vhost['php'],
                ":cgi" => ($vhost['cgi'] == 1 ? 1 : 0),
                ":ssl" => $vhost['ssl'],
                ":hsts" => $hsts,
                ":suexec_user" => $suexec_user,
                ":server" => $server,
                ":logtype" => ($vhost['logtype'] ? $vhost['logtype'] : NULL),
                ":errorlog" => (int) $vhost['errorlog'],
                ":cert" => $cert,
                ":ipv4" => $ipv4,
                ":autoipv6" => $autoipv6,
                ":options" => $vhost['options'],
                ":stats" => ($vhost['stats'] ? $vhost['stats'] : NULL),
                ":id" => $id);
  if ($id != 0) {
    logger(LOG_INFO, 'modules/vhosts/include/vhosts', 'vhosts', 'Updating vhost #'.$id.' ('.$vhost['hostname'].'.'.$vhost['domain'].')');
    db_query("UPDATE vhosts.vhost SET hostname=:hostname, domain=:domain, docroot=:docroot, php=:php, cgi=:cgi, `ssl`=:ssl, hsts=:hsts, `suexec_user`=:suexec_user, `server`=:server, logtype=:logtype, errorlog=:errorlog, certid=:cert, ipv4=:ipv4, autoipv6=:autoipv6, options=:options, stats=:stats WHERE id=:id", $args);
  }
  else {
    $args[":user"] = $_SESSION['userinfo']['uid'];
    unset($args[":id"]);
    logger(LOG_INFO, 'modules/vhosts/include/vhosts', 'vhosts', 'Creating vhost '.$vhost['hostname'].'.'.$vhost['domain'].'');
    $result = db_query("INSERT INTO vhosts.vhost (user, hostname, domain, docroot, php, cgi, `ssl`, hsts, `suexec_user`, `server`, logtype, errorlog, certid, ipv4, autoipv6, options, stats) VALUES ".
                       "(:user, :hostname, :domain, :docroot, :php, :cgi, :ssl, :hsts, :suexec_user, :server, :logtype, :errorlog, :cert, :ipv4, :autoipv6, :options, :stats)", $args);
    $id = db_insert_id();
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
  $result = db_query("SELECT * FROM vhosts.v_alias WHERE id=?", array($id));
  
  if ($result->rowCount() != 1)
    system_failure('Interner Fehler beim Auslesen der Alias-Daten');
  
  $alias = $result->fetch();
  
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

  logger(LOG_INFO, 'modules/vhosts/include/vhosts', 'aliases', 'Removing alias #'.$id.' ('.$alias['hostname'].'.'.$alias['domain'].')');
  db_query("DELETE FROM vhosts.alias WHERE id=?", array($id));
}

function save_alias($alias)
{
  if (! is_array($alias))
    system_failure('$alias kein array!');
  $id = (isset($alias['id']) ? (int) $alias['id'] : 0);
  $domain = (int) $alias['domain_id'];
  if ($domain == 0)
    system_failure('$domain == 0');
  if ($alias['domain_id'] == -1)
    $domain = NULL;
  $vhost = get_vhost_details( (int) $alias['vhost']);
  $args = array(":hostname" => $alias['hostname'],
                ":domain" => $domain,
                ":vhost" => $vhost['id'],
                ":options" => $alias['options'],
                ":id" => $id);
  if ($id == 0) {
    unset($args[":id"]);
    logger(LOG_INFO, 'modules/vhosts/include/vhosts', 'aliases', 'Creating alias '.$alias['hostname'].'.'.$alias['domain'].' for VHost '.$vhost['id']);
    db_query("INSERT INTO vhosts.alias (hostname, domain, vhost, options) VALUES (:hostname, :domain, :vhost, :options)", $args);
  }
  else {
    unset($args[":vhost"]);
    logger(LOG_INFO, 'modules/vhosts/include/vhosts', 'aliases', 'Updating alias #'.$id.' ('.$alias['hostname'].'.'.$alias['domain'].')');
    db_query("UPDATE vhosts.alias SET hostname=:hostname, domain=:domain, options=:options WHERE id=:id", $args);
  }
}


function available_suexec_users()
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT uid, username FROM vhosts.available_users LEFT JOIN vhosts.v_useraccounts ON (uid = suexec_user) WHERE mainuser=?", array($uid));
  $ret = array();
  while ($i = $result->fetch())
    $ret[] = $i;
  DEBUG('available suexec-users:');
  DEBUG($ret);
  return $ret;

}


function user_ipaddrs()
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT ipaddr FROM vhosts.ipaddr_available WHERE uid=?", array($uid));
  $ret = array();
  while ($i = $result->fetch())
  {
    $ret[] = $i['ipaddr'];
  }
  DEBUG($ret);
  return $ret;
}


?>

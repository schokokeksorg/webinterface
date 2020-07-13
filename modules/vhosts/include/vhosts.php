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

require_once("inc/base.php");
require_once("inc/error.php");
require_once("inc/security.php");

require_once('class/domain.php');

require_once("certs.php");


function valid_php_versions()
{
    /* In der konfiguration ist die Variable "php_versions" eine kommaseparierte Liste der unterstützten Versionen.
        Dabei gelten folgende Suffixe (nicht kombinierbar!):
            *: Standardversion für neue Konfigurationen
            /: Deprecated
            +: Beta-Version
    */
    $tags = explode(',', config('php_versions'));
    $ret = array();
    foreach ($tags as $t) {
        $key = $t;
        $ver = array('major' => null, 'minor' => null, 'status' => 'regular', 'default' => false);
        if (substr($t, -1, 1) == '+') {
            $ver['status'] = 'beta';
            $key = substr($t, 0, -1);
        } elseif (substr($t, -1, 1) == '/') {
            $ver['status'] = 'deprecated';
            $key = substr($t, 0, -1);
        } elseif (substr($t, -1, 1) == '*') {
            $ver['default'] = true;
            $key = substr($t, 0, -1);
        }

        /* Wir nehmen an, dass unsere Tags immer an zweitletzter Stelle die Major-Version und
        an letzter Stelle die Minor-Version enthalten */
        $ver['major'] = substr($key, -2, 1);
        $ver['minor'] = substr($key, -1, 1);
        $ret[$key] = $ver;
    }
    /* Bis hier: aus der Datenbank ausgelesen */
    krsort($ret);
    DEBUG($ret);
    /* Sonderfall: Wenn ein User noch Vhosts einer anderen Version hat, dann bleibt diese erlaubt */
    $list = list_vhosts();
    foreach ($list as $vhost) {
        if ($vhost['php'] && $vhost['php'] != 'default' && !array_key_exists($vhost['php'], $ret)) {
            $key = $vhost['php'];
            $ret = array($key => array('major' => null, 'minor' => null, 'status' => 'used', 'default' => false)) + $ret;
            /* Wir nehmen an, dass unsere Tags immer an zweitletzter Stelle die Major-Version und
            an letzter Stelle die Minor-Version enthalten */
            $ret[$key]['major'] = substr($key, -2, 1);
            $ret[$key]['minor'] = substr($key, -1, 1);
        }
    }
    return $ret;
}


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
    if (!$data['v6_prefix']) {
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


function list_vhosts($filter=null)
{
    $uid = (int) $_SESSION['userinfo']['uid'];
    $query = "SELECT vh.id,fqdn,domain,docroot,docroot_is_default,php,cgi,vh.certid AS cert, vh.ssl, vh.options,logtype,errorlog,IF(dav.id IS NULL OR dav.type='svn', 0, 1) AS is_dav,IF(dav.id IS NULL OR dav.type='dav', 0, 1) AS is_svn, IF(webapps.id IS NULL, 0, 1) AS is_webapp FROM vhosts.v_vhost AS vh LEFT JOIN vhosts.dav ON (dav.vhost=vh.id) LEFT JOIN vhosts.webapps ON (webapps.vhost = vh.id) WHERE uid=:uid ORDER BY domain,hostname";
    $params = array(":uid" => $uid);
    if ($filter) {
        $query = "SELECT vh.id,fqdn,domain,docroot,docroot_is_default,php,cgi,vh.certid AS cert, vh.ssl, vh.options,logtype,errorlog,IF(dav.id IS NULL OR dav.type='svn', 0, 1) AS is_dav,IF(dav.id IS NULL OR dav.type='dav', 0, 1) AS is_svn, IF(webapps.id IS NULL, 0, 1) AS is_webapp FROM vhosts.v_vhost AS vh LEFT JOIN vhosts.dav ON (dav.vhost=vh.id) LEFT JOIN vhosts.webapps ON (webapps.vhost = vh.id) WHERE (vh.fqdn LIKE :filter OR vh.id IN (SELECT vhost FROM vhosts.v_alias WHERE fqdn LIKE :filter)) AND uid=:uid ORDER BY hostname";
        $params[":filter"] = "%$filter%";
    }
    $result = db_query($query, $params);
    $ret = array();
    while ($item = $result->fetch()) {
        array_push($ret, $item);
    }
    return $ret;
}

function ipv6_possible($server)
{
    $args = array(":server" => $server);
    $result = db_query("SELECT v6_prefix FROM system.servers WHERE id=:server OR hostname=:server", $args);
    $line = $result->fetch();
    DEBUG("Server {$server} is v6-capable: ". ($line['v6_prefix'] != null));
    return ($line['v6_prefix'] != null);
}

function empty_vhost()
{
    $vhost['id'] = null;
    $vhost['hostname'] = null;

    $vhost['domain_id'] = null;
    $vhost['domain'] = null;

    $vhost['homedir'] = $_SESSION['userinfo']['homedir'];
    $vhost['docroot'] = null;

    $vhost['php'] = 'default';
    $vhost['cgi'] = 1;
    $vhost['ssl'] = null;
    $vhost['hsts'] = -1;
    $vhost['suexec_user'] = null;
    $vhost['server'] = null;
    $vhost['logtype'] = null;
    $vhost['errorlog'] = 0;
    $vhost['is_dav'] = 0;
    $vhost['is_svn'] = 0;
    $vhost['is_webapp'] = 0;
    $vhost['webapp_id'] = null;

    $vhost['cert'] = null;
    $vhost['certid'] = null;
    $vhost['ipv4'] = null;
    $vhost['autoipv6'] = 2; // 1 => Eine IP pro User, 2 => Eine IP pro VHost

    $vhost['options'] = 'forwardwww';
    return $vhost;
}


function empty_alias()
{
    $alias['hostname'] = null;

    $alias['domain_id'] = -1;
    $alias['domain'] = $_SESSION['userinfo']['username'].'.'.config('masterdomain');

    $alias['options'] = null;
    return $alias;
}


function userdomain()
{
    if (config('user_vhosts_domain') === null) {
        return null;
    }
    $result = db_query("SELECT id,name FROM vhosts.v_domains WHERE name=:dom", array(":dom" => config('user_vhosts_domain')));
    $res = $result->fetch();
    return $res;
}

function domainselect($selected = null, $selectattribute = '')
{
    global $domainlist, $config;
    if ($domainlist == null) {
        $uid = null;
        if (isset($_SESSION['userinfo']['uid'])) {
            $uid = $_SESSION['userinfo']['uid'];
        }
        $domainlist = get_domain_list(null, $uid);
    }
    $selected = (int) $selected;

    $ret = '<select id="domain" name="domain" size="1" '.$selectattribute.' >';
    $found = false;
    foreach ($domainlist as $dom) {
        $s = '';
        if ($selected == $dom->id) {
            $s = ' selected="selected" ';
            $found = true;
        }
        $ret .= "<option value=\"{$dom->id}\"{$s}>{$dom->fqdn}</option>\n";
    }
    $userdomain = userdomain();
    $ret .= ' <option value="" disabled="disabled">--------------------------------</option>';
    if ($userdomain) {
        $s = ($selected == -1 ? ' selected="selected"' : '');
        $ret .= ' <option value="-1"'.$s.'>'.$_SESSION['userinfo']['username'].'.'.$userdomain['name'].'</option>';
    }
    if ($selected == -2) {
        $s = ($selected == -2 ? ' selected="selected"' : '');
        $ret .= ' <option value="-2"'.$s.'>'.$_SESSION['userinfo']['username'].'.'.config('masterdomain').' (Bitte nicht mehr benutzen!)</option>';
        if ($selected > 0 and ! $found) {
            system_failure("Hier wird eine Domain benutzt, die nicht zu diesem Benutzeraccount gehört. Bearbeiten würde Daten zerstören!");
        }
    }
    $ret .= '</select>';
    return $ret;
}



function get_vhost_details($id)
{
    DEBUG("Lese #{$id}...");
    $id = (int) $id;
    $uid = (int) $_SESSION['userinfo']['uid'];
    $result = db_query("SELECT vh.*,IF(dav.id IS NULL OR dav.type='svn', 0, 1) AS is_dav,IF(dav.id IS NULL OR dav.type='dav', 0, 1) AS is_svn, IF(webapps.id IS NULL, 0, 1) AS is_webapp FROM vhosts.v_vhost AS vh LEFT JOIN vhosts.dav ON (dav.vhost=vh.id) LEFT JOIN vhosts.webapps ON (webapps.vhost = vh.id) WHERE uid=:uid AND vh.id=:id", array(":uid" => $uid, ":id" => $id));
    if ($result->rowCount() != 1) {
        system_failure('Interner Fehler beim Auslesen der Daten');
    }

    $ret = $result->fetch();

    if ($ret['domain_id'] === null) {
        $ret['domain_id'] = -2;
    }
    $ret['cert'] = $ret['certid'];
    $userdomain = userdomain();
    if ($ret['domain_id'] == $userdomain['id']) {
        $user = $_SESSION['userinfo']['username'];
        $ret['domain_id'] = -1;
        if ($ret['hostname'] == $user) {
            $ret['hostname'] = null;
        } elseif (substr($ret['hostname'], -strlen($user), strlen($user)) == $user) {
            $ret['hostname'] = substr($ret['hostname'], 0, -strlen($user)-1); // Punkt mit entfernen!
        } else {
            system_failure('Userdomain ohne Username!');
        }
    }
    if ($ret['hsts'] === null) {
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
        array_push($ret, array('id' => 'www', 'fqdn' => 'www.'.$vhost['fqdn'], 'options' => (strstr($vhost['options'], 'forwardwww') ? 'forward' : null)));
    }
    foreach ($aliases as $item) {
        array_push($ret, $item);
        if (strstr($item['options'], 'aliaswww')) {
            array_push($ret, array('id' => 'www_'.$item['id'], 'fqdn' => 'www.'.$item['fqdn'], 'options' => (strstr($item['options'], 'forward') ? 'forward' : null)));
        }
    }
    return $ret;
}


function list_available_webapps()
{
    $result = db_query("SELECT id,displayname FROM vhosts.global_webapps");
    $ret = array();
    while ($item = $result->fetch()) {
        array_push($ret, $item);
    }
    return $ret;
}


function delete_vhost($id)
{
    $id = (int) $id;
    if ($id == 0) {
        system_failure("id == 0");
    }
    $vhost = get_vhost_details($id);
    logger(LOG_INFO, 'modules/vhosts/include/vhosts', 'vhosts', 'Removing vhost #'.$id.' ('.$vhost['hostname'].'.'.$vhost['domain'].')');
    db_query("DELETE FROM vhosts.vhost WHERE id=?", array($vhost['id']));
}



function make_svn_vhost($id)
{
    $id = (int) $id;
    if ($id == 0) {
        system_failure("id == 0");
    }
    logger(LOG_INFO, 'modules/vhosts/include/vhosts', 'vhosts', 'Converting vhost #'.$id.' to SVN');
    db_query("REPLACE INTO vhosts.dav (vhost, type) VALUES (?, 'svn')", array($id));
    db_query("DELETE FROM vhosts.webapps WHERE vhost=?", array($id));
}

function make_dav_vhost($id)
{
    $id = (int) $id;
    if ($id == 0) {
        system_failure("id == 0");
    }
    logger(LOG_INFO, 'modules/vhosts/include/vhosts', 'vhosts', 'Converting vhost #'.$id.' to WebDAV');
    db_query("REPLACE INTO vhosts.dav (vhost, type, options) VALUES (?, 'dav', 'nouserfile')", array($id));
    db_query("DELETE FROM vhosts.webapps WHERE vhost=?", array($id));
}

function make_regular_vhost($id)
{
    $id = (int) $id;
    if ($id == 0) {
        system_failure("id == 0");
    }
    logger(LOG_INFO, 'modules/vhosts/include/vhosts', 'vhosts', 'Converting vhost #'.$id.' to regular');
    db_query("DELETE FROM vhosts.dav WHERE vhost=?", array($id));
    db_query("DELETE FROM vhosts.webapps WHERE vhost=?", array($id));
}


function make_webapp_vhost($id, $webapp)
{
    $id = (int) $id;
    $webapp = (int) $webapp;
    if ($id == 0) {
        system_failure("id == 0");
    }
    $result = db_query("SELECT displayname FROM vhosts.global_webapps WHERE id=?", array($webapp));
    if ($result->rowCount() == 0) {
        system_failure("webapp-id invalid");
    }
    $webapp_name = $result->fetch(PDO::FETCH_OBJ)->displayname;
    logger(LOG_INFO, 'modules/vhosts/include/vhosts', 'vhosts', 'Setting up webapp '.$webapp_name.' on vhost #'.$id);
    db_query("REPLACE INTO vhosts.webapps (vhost, webapp) VALUES (?, ?)", array($id, $webapp));
    mail('webapps-setup@schokokeks.org', 'setup', 'setup');
}


function check_hostname_collision($hostname, $domain)
{
    $uid = (int) $_SESSION['userinfo']['uid'];
    # Neuer vhost => Prüfe Duplikat
    $args = array(":hostname" => $hostname, ":domain" => $domain, ":uid" => $uid);
    $domaincheck = "domain=:domain";
    if ($domain == -1) {
        $userdomain = userdomain();
        if ($hostname) {
            $hostname .= ".".$_SESSION['userinfo']['username'];
        }
        $args[":domain"] = $userdomain['id'];
    }
    if ($domain == -2) {
        unset($args[":domain"]);
        $domaincheck = "domain IS NULL";
    }
    $hostnamecheck = "hostname=:hostname";
    if (! $hostname) {
        $hostnamecheck = "hostname IS NULL";
        unset($args[":hostname"]);
    }
    $result = db_query("SELECT id FROM vhosts.vhost WHERE {$hostnamecheck} AND {$domaincheck} AND user=:uid", $args);
    if ($result->rowCount() > 0) {
        system_failure('Eine Konfiguration mit diesem Namen gibt es bereits.');
    }
    if ($domain <= -1) {
        return ;
    }
    unset($args[":uid"]);
    $result = db_query("SELECT id, vhost FROM vhosts.v_alias WHERE {$hostnamecheck} AND {$domaincheck}", $args);
    if ($result->rowCount() > 0) {
        $data = $result->fetch();
        $vh = get_vhost_details($data['vhost']);
        system_failure('Dieser Hostname ist bereits als Alias für »'.$vh['fqdn'].'« eingerichtet');
    }
}

function save_vhost($vhost)
{
    if (! is_array($vhost)) {
        system_failure('$vhost kein array!');
    }
    $id = (int) $vhost['id'];
    $hostname = $vhost['hostname'];
    $domain = (int) $vhost['domain_id'];
    if ($domain == 0) {
        system_failure('$domain == 0');
    }
    if ($vhost['domain_id'] == -2) {
        $domain = null;
    }
    if ($id == 0) {
        check_hostname_collision($vhost['hostname'], $vhost['domain_id']);
    }
    $hsts = (int) $vhost['hsts'];
    if ($hsts < 0) {
        $hsts = null;
    }
    $suexec_user = null;

    $available_suexec = available_suexec_users();
    foreach ($available_suexec as $u) {
        if ($u['uid'] == $vhost['suexec_user']) {
            $suexec_user = $u['uid'];
        }
    }

    $server = null;
    $available_servers = additional_servers();
    if (in_array($vhost['server'], $available_servers)) {
        $server = (int) $vhost['server'];
    }
    if ($server == my_server_id()) {
        $server = null;
    }

    if ($vhost['is_svn']) {
        if (! $vhost['options']) {
            $vhost['options']='nodocroot';
        } else {
            $vhost['options'].=",nodocroot";
        }
    }

    $cert = null;
    $certs = user_certs();
    foreach ($certs as $c) {
        if ($c['id'] == $vhost['cert']) {
            $cert = $c['id'];
        }
    }

    $ipv4 = null;
    $ipv4_avail = user_ipaddrs();
    if (in_array($vhost['ipv4'], $ipv4_avail)) {
        $ipv4 = $vhost['ipv4'];
    }

    $autoipv6 = 1;
    if ($vhost['autoipv6'] == 0 ||  $vhost['autoipv6'] == 2) {
        $autoipv6 = $vhost['autoipv6'];
    }

    if (!($vhost['ssl'] == 'forward' || $vhost['ssl'] == 'http' ||
        $vhost['ssl'] == 'https')) {
        $vhost['ssl'] = null;
    }

    $args = array(":hostname" => ($hostname ? $hostname : null),
                ":domain" => $domain,
                ":docroot" => ($vhost['docroot'] ? $vhost['docroot'] : null),
                ":php" => $vhost['php'],
                ":cgi" => ($vhost['cgi'] == 1 ? 1 : 0),
                ":ssl" => $vhost['ssl'],
                ":hsts" => $hsts,
                ":suexec_user" => $suexec_user,
                ":server" => $server,
                ":logtype" => ($vhost['logtype'] ? $vhost['logtype'] : null),
                ":errorlog" => (int) $vhost['errorlog'],
                ":cert" => $cert,
                ":ipv4" => $ipv4,
                ":autoipv6" => $autoipv6,
                ":options" => $vhost['options'],
                ":id" => $id);
    if ($id != 0) {
        logger(LOG_INFO, 'modules/vhosts/include/vhosts', 'vhosts', 'Updating vhost #'.$id.' ('.$vhost['hostname'].'.'.$vhost['domain'].')');
        db_query("UPDATE vhosts.vhost SET hostname=:hostname, domain=:domain, docroot=:docroot, php=:php, cgi=:cgi, `ssl`=:ssl, hsts=:hsts, `suexec_user`=:suexec_user, `server`=:server, logtype=:logtype, errorlog=:errorlog, certid=:cert, ipv4=:ipv4, autoipv6=:autoipv6, options=:options WHERE id=:id", $args);
    } else {
        $args[":user"] = $_SESSION['userinfo']['uid'];
        unset($args[":id"]);
        logger(LOG_INFO, 'modules/vhosts/include/vhosts', 'vhosts', 'Creating vhost '.$vhost['hostname'].'.'.$vhost['domain'].'');
        $result = db_query("INSERT INTO vhosts.vhost (user, hostname, domain, docroot, php, cgi, `ssl`, hsts, `suexec_user`, `server`, logtype, errorlog, certid, ipv4, autoipv6, options) VALUES ".
                       "(:user, :hostname, :domain, :docroot, :php, :cgi, :ssl, :hsts, :suexec_user, :server, :logtype, :errorlog, :cert, :ipv4, :autoipv6, :options)", $args, true);
        $id = db_insert_id();
    }
    $oldvhost = get_vhost_details($id);
    /*
      these vars may be 0 or 1.
      So newval > oldval means that it has been switched on yet.
    */
    if ($vhost['is_dav'] > $oldvhost['is_dav']) {
        make_dav_vhost($id);
    } elseif ($vhost['is_svn'] > $oldvhost['is_svn']) {
        make_svn_vhost($id);
    } elseif ($vhost['is_webapp'] > $oldvhost['is_webapp']) {
        make_webapp_vhost($id, $vhost['webapp_id']);
    } elseif ($vhost['is_dav'] == 0 && $vhost['is_svn'] == 0 && $vhost['is_webapp'] == 0) {
        make_regular_vhost($id);
    }
}


function get_alias_details($id)
{
    $id = (int) $id;
    $uid = (int) $_SESSION['userinfo']['uid'];
    $result = db_query("SELECT * FROM vhosts.v_alias WHERE id=?", array($id));

    if ($result->rowCount() != 1) {
        system_failure('Interner Fehler beim Auslesen der Alias-Daten');
    }

    $alias = $result->fetch();

    if ($alias['domain_id'] == null) {
        $alias['domain_id'] = -1;
    }

    /* Das bewirkt, dass nur die eigenen Aliase gesehen werden können */
    get_vhost_details((int) $alias['vhost']);

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
    if (! is_array($alias)) {
        system_failure('$alias kein array!');
    }
    $id = (isset($alias['id']) ? (int) $alias['id'] : 0);
    $domain = (int) $alias['domain_id'];
    if ($domain == 0) {
        system_failure('$domain == 0');
    }
    if ($alias['domain_id'] == -2) {
        $domain = null;
    }
    $vhost = get_vhost_details((int) $alias['vhost']);
    if (! $alias['hostname']) {
        $alias['hostname'] = null;
    }
    $args = array(":hostname" => $alias['hostname'],
                ":domain" => $domain,
                ":vhost" => $vhost['id'],
                ":options" => $alias['options'],
                ":id" => $id);
    if ($id == 0) {
        unset($args[":id"]);
        logger(LOG_INFO, 'modules/vhosts/include/vhosts', 'aliases', 'Creating alias '.$alias['hostname'].'.'.$alias['domain'].' for VHost '.$vhost['id']);
        db_query("INSERT INTO vhosts.alias (hostname, domain, vhost, options) VALUES (:hostname, :domain, :vhost, :options)", $args, true);
    } else {
        unset($args[":vhost"]);
        logger(LOG_INFO, 'modules/vhosts/include/vhosts', 'aliases', 'Updating alias #'.$id.' ('.$alias['hostname'].'.'.$alias['domain'].')');
        db_query("UPDATE vhosts.alias SET hostname=:hostname, domain=:domain, options=:options WHERE id=:id", $args, true);
    }
}


function available_suexec_users()
{
    $uid = (int) $_SESSION['userinfo']['uid'];
    $result = db_query("SELECT uid, username FROM vhosts.available_users LEFT JOIN vhosts.v_useraccounts ON (uid = suexec_user) WHERE mainuser=?", array($uid));
    $ret = array();
    while ($i = $result->fetch()) {
        $ret[] = $i;
    }
    DEBUG('available suexec-users:');
    DEBUG($ret);
    return $ret;
}


function user_ipaddrs()
{
    $uid = (int) $_SESSION['userinfo']['uid'];
    $result = db_query("SELECT ipaddr FROM vhosts.ipaddr_available WHERE uid=?", array($uid));
    $ret = array();
    while ($i = $result->fetch()) {
        $ret[] = $i['ipaddr'];
    }
    DEBUG($ret);
    return $ret;
}

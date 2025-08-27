<?php

/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/debug.php');
require_once('inc/base.php');
require_once('inc/security.php');
require_once('inc/error.php');

require_once('class/domain.php');

$caa_properties = [ 0 => "issue", 1 => "issuewild", 2 => "iodef" ];

function get_dyndns_accounts()
{
    $uid = (int) $_SESSION['userinfo']['uid'];
    $result = db_query("SELECT * FROM dns.dyndns WHERE uid=?", [$uid]);
    $list = [];
    while ($item = $result->fetch()) {
        array_push($list, $item);
    }
    DEBUG($list);
    return $list;
}


function get_dyndns_account($id, $ignore = true)
{
    $args = [":id" => (int) $id,
        ":uid" => (int) $_SESSION['userinfo']['uid'], ];
    $result = db_query("SELECT * FROM dns.dyndns WHERE id=:id AND uid=:uid", $args);
    if ($result->rowCount() != 1) {
        if ($ignore) {
            return null;
        }
        logger(LOG_WARNING, "modules/dns/include/dnsinclude", "dyndns", "account »{$id}« invalid for uid »{$_SESSION['userinfo']['uid']}«.");
        system_failure("Account ungültig");
    }
    $item = $result->fetch();
    DEBUG($item);
    return $item;
}


function create_dyndns_account($handle, $password_http, $sshkey)
{
    $uid = (int) $_SESSION['userinfo']['uid'];

    if ($password_http == '' && $sshkey == '') {
        system_failure('Sie müssen entweder einen SSH-Key oder ein Passwort zum Web-Update eingeben.');
    }

    $handle = verify_input_identifier($handle);

    if (strlen(trim($sshkey)) == 0) {
        $sshkey = null;
    } else {
        $sshkey = filter_ssh_key($sshkey);
    }

    $pwhash = null;
    if ($password_http) {
        if (($check = strong_password($password_http)) !== true) {
            system_failure($check);
        }
        $pwhash = gen_pw_hash($password_http);
    }

    db_query(
        "INSERT INTO dns.dyndns (uid, handle, password, sshkey) VALUES "
           . "(:uid, :handle, :pwhash, :sshkey)",
        [":uid" => $uid, ":handle" => $handle, ":pwhash" => $pwhash, ":sshkey" => $sshkey]
    );
    $dyndns_id = db_insert_id();
    //$masterdomain = new Domain(config('masterdomain'));
    //db_query("INSERT INTO dns.custom_records (type, domain, hostname, dyndns, ttl) VALUES ".
    //         "('a', :dom, :hostname, :dyndns, 120)",
    //         array(":dom" => $masterdomain->id, ":hostname" => filter_input_hostname($handle).'.'.$_SESSION['userinfo']['username'], ":dyndns" => $dyndns_id));
    logger(LOG_INFO, "modules/dns/include/dnsinclude", "dyndns", "inserted account {$dyndns_id}");
    return $dyndns_id;
}


function edit_dyndns_account($id, $handle, $password_http, $sshkey)
{
    $id = (int) $id;
    $oldaccount = get_dyndns_account($id);
    $handle = verify_input_identifier($handle);
    if (trim($sshkey) == '') {
        $sshkey = null;
    } else {
        $sshkey = filter_ssh_key($sshkey);
    }

    $args = [":handle" => $handle, ":sshkey" => $sshkey, ":id" => $id];
    $pwhash = null;
    if ($password_http && $password_http != '************') {
        if (($check = strong_password($password_http)) !== true) {
            system_failure($check);
        }
        $args[":pwhash"] = gen_pw_hash($password_http);
        db_query("UPDATE dns.dyndns SET handle=:handle, password=:pwhash, sshkey=:sshkey WHERE id=:id", $args);
    } else {
        db_query("UPDATE dns.dyndns SET handle=:handle, sshkey=:sshkey WHERE id=:id", $args);
    }
    logger(LOG_INFO, "modules/dns/include/dnsinclude", "dyndns", "edited account »{$id}«");
}


function delete_dyndns_account($id)
{
    $id = (int) $id;

    db_query("DELETE FROM dns.dyndns WHERE id=?", [$id]);
    logger(LOG_INFO, "modules/dns/include/dnsinclude", "dyndns", "deleted account »{$id}«");
}


function get_dyndns_records($id)
{
    $id = (int) $id;
    $result = db_query("SELECT hostname, domain, type, ttl, lastchange, id FROM dns.custom_records WHERE dyndns=?", [$id]);
    $data = [];
    while ($entry = $result->fetch()) {
        $dom = new Domain((int) $entry['domain']);
        if ($dom->fqdn != config('masterdomain') && $dom->fqdn != config('user_vhosts_domain')) {
            $dom->ensure_userdomain();
        }
        $entry['fqdn'] = $entry['hostname'] . '.' . $dom->fqdn;
        if (!$entry['hostname']) {
            $entry['fqdn'] = $dom->fqdn;
        }
        array_push($data, $entry);
    }
    DEBUG($data);
    return $data;
}

$valid_record_types = ['a', 'aaaa', 'mx', 'ns', 'txt', 'cname', 'ptr', 'srv', 'sshfp', 'caa', 'https', 'raw'];


function blank_dns_record($type)
{
    global $valid_record_types;
    if (!in_array(strtolower($type), $valid_record_types)) {
        system_failure('invalid type: ' . $type);
    }
    $rec = ['hostname' => null,
        'domain' => 0,
        'type' => strtolower($type),
        'ttl' => 3600,
        'ip' => null,
        'dyndns' => null,
        'data' => null,
        'spec' => null, ];
    if (strtolower($type) == 'mx') {
        $rec['data'] = config('default_mx');
        $rec['spec'] = '5';
    }
    return $rec;
}

function get_dns_record($id)
{
    $id = (int) $id;
    $result = db_query("SELECT hostname, domain, type, ip, dyndns, spec, data, ttl FROM dns.custom_records WHERE id=?", [$id]);
    if ($result->rowCount() != 1) {
        system_failure('illegal ID');
    }
    $data = $result->fetch();
    $dom = new Domain((int) $data['domain']);
    $dom->ensure_userdomain();
    DEBUG($data);
    return $data;
}


function get_domain_records($dom)
{
    $dom = (int) $dom;
    $result = db_query("SELECT hostname, domain, type, ip, dyndns, spec, data, ttl, id FROM dns.custom_records WHERE domain=?", [$dom]);
    $data = [];
    while ($entry = $result->fetch()) {
        $dom = new Domain((int) $entry['domain']);
        $dom->ensure_userdomain();
        $entry['fqdn'] = $entry['hostname'] . '.' . $dom->fqdn;
        if (!$entry['hostname']) {
            $entry['fqdn'] = $dom->fqdn;
        }
        array_push($data, $entry);
    }
    DEBUG($data);
    return $data;
}

function get_domain_auto_records($domainname)
{
    $result = db_query("SELECT hostname, domain, CONCAT_WS('.', hostname, domain) AS fqdn, type, ip, spec, data, ttl FROM dns.tmp_autorecords WHERE domain=?", [$domainname]);
    $data = [];
    while ($entry = $result->fetch()) {
        array_push($data, $entry);
    }
    DEBUG($data);
    return $data;
}


function warn_autorecord_collission($hostname, $domain, $type, $data)
{
    $autorecords = get_domain_auto_records($domain);
    foreach ($autorecords as $ar) {
        if (is_string($data) && !str_starts_with($data, "v=spf1") && $hostname == null) {
            // Spezialfall SPF-Record
            continue;
        }
        if ($ar['hostname'] == $hostname && $ar['type'] == $type) {
            warning('Sie haben einen DNS-Record angelegt, für den bisher ein automatisch erzeuger Record vorhanden war. Ihr neuer Eintrag wird den bisherigen ersetzen. Bitte haben Sie einen Moment Geduld und laden Sie diese Seite in wenigen Minuten neu. Der automatisch erzeute Record sollte dann verschwunden sein.');
            break;
        }
    }
}


$implemented_record_types = ['a', 'aaaa', 'mx', 'ns', 'txt', 'cname', 'ptr', 'srv', 'sshfp', 'caa', 'https'];

function save_dns_record($id, $record)
{
    global $valid_record_types;
    global $implemented_record_types;
    $record['type'] = strtolower($record['type']);
    if (!in_array($record['type'], $valid_record_types)) {
        system_failure('invalid type: ' . $record['type']);
    }
    if (!in_array($record['type'], $implemented_record_types)) {
        system_failure('record type ' . $record['type'] . ' not implemented at the moment.');
    }
    $dom = new Domain((int) $record['domain']);
    $dom->ensure_userdomain();
    if (!$dom->id) {
        system_failure('invalid domain');
    }
    if ($record['hostname'] == '') {
        $record['hostname'] = null;
    }
    verify_input_hostname($record['hostname'], true);
    /* HTTPS record type allows quotes, we check format below */
    if ($record['type'] != 'https') {
        verify_input_recorddata($record['data']);
    }
    if ($record['ttl'] && (int) $record['ttl'] < 1) {
        system_failure('Fehler bei TTL');
    }
    warn_autorecord_collission($record['hostname'], $dom->fqdn, $record['type'], $record['data']);
    switch ($record['type']) {
        case 'a':
            if ($record['dyndns']) {
                get_dyndns_account($record['dyndns']);
                $record['ip'] = null;
            } else {
                verify_input_ipv4($record['ip']);
                $record['data'] = null;
                $record['spec'] = null;
            }
            break;
        case 'aaaa':
            if ($record['dyndns']) {
                get_dyndns_account($record['dyndns']);
                $record['ip'] = null;
            } else {
                $record['dyndns'] = null;
                verify_input_ipv6($record['ip']);
                $record['data'] = null;
                $record['spec'] = null;
            }
            break;
        case 'mx':
            $record['dyndns'] = null;
            $record['spec'] = (int) $record['spec'];
            if ($record['spec'] < 0) {
                system_failure("invalid priority");
            }
            if (strlen($record['data']) > 255) {
                system_failure('data field is too long');
            }
            verify_input_hostname($record['data']);
            if (!$record['data']) {
                system_failure('MX hostname missing');
            }
            $record['ip'] = null;
            break;
        case 'ptr':
        case 'ns':
            if (!$record['hostname']) {
                system_failure("Die angestrebte Konfiguration wird nicht funktionieren, Speichern wurde daher verweigert.");
            }
            // no break
        case 'cname':
            $record['dyndns'] = null;
            $record['spec'] = null;
            $record['ip'] = null;
            if (strlen($record['data']) > 255) {
                system_failure('data field is too long');
            }
            verify_input_hostname($record['data']);
            if (!$record['data']) {
                system_failure('destination host missing');
            }
            break;

        case 'spf':
        case 'txt':
            $record['dyndns'] = null;
            $record['spec'] = null;
            $record['ip'] = null;
            if (strlen($record['data']) > 1024) {
                system_failure('data field is too long');
            }
            if (!$record['data']) {
                system_failure('text entry missing');
            }
            break;

        case 'sshfp':
            $record['dyndns'] = null;
            $record['spec'] = max((int) $record['spec'], 1);
            $record['ip'] = null;
            if (strlen($record['data']) > 255) {
                system_failure('data field is too long');
            }
            if (!$record['data']) {
                system_failure('text entry missing');
            }
            break;

        case 'caa':
            $record['dyndns'] = null;
            $record['ip'] = null;
            if (strlen($record['data']) > 255) {
                system_failure('data field is too long');
            }
            if (!$record['data']) {
                system_failure('text entry missing');
            }
            break;

        case 'srv':
            $record['dyndns'] = null;
            $record['spec'] = (int) $record['spec'];
            if ($record['spec'] < 0) {
                system_failure("invalid priority");
            }
            if (strlen($record['data']) > 255) {
                system_failure('data field is too long');
            }
            if (!$record['data']) {
                system_failure('SRV target missing');
            }
            $data = explode(':', $record['data']);
            if (count($data) != 2) {
                system_failure('Das eingegebene Ziel war nicht im Format hostname:port');
            }
            [$hostname, $port] = $data;
            verify_input_hostname($hostname);
            if ($port !== (string) (int) $port || (int) $port < 1 || (int) $port > 65535) {
                system_failure('Ungültige Portnummer');
            }
            $record['ip'] = null;
            break;

        case 'https':
            $record['dyndns'] = null;
            $record['ip'] = null;
            $record['spec'] = (int) $record['spec'];
            if ($record['spec'] < 0) {
                system_failure("invalid priority");
            }
            if ((!$record['data']) || (strlen($record['data']) == 0)) {
                system_failure('data is missing');
            }
            if (strlen($record['data']) > 255) {
                system_failure('data field is too long');
            }
            $data = explode(' ', $record['data']);
            $host = array_shift($data);
            if ($host != "." && !filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                system_failure("Ungültiger Hostname!");
            }
            foreach ($data as $d) {
                if (!(preg_match('/[a-z0-9]+=([a-z0-9,:.]+|"[a-z0-9,:.]+")/', $d))) {
                    system_failure("Ungültiger HTTPS record!");
                }
            }
            break;

        default:
            system_failure('Not implemented');
    }
    $id = (int) $id;
    $args = [":domain" => $dom->id,
        ":hostname" => $record['hostname'],
        ":type" => $record['type'],
        ":ttl" => ($record['ttl'] == 0 ? null : (int) $record['ttl']),
        ":ip" => $record['ip'],
        ":dyndns" => $record['dyndns'],
        ":data" => $record['data'],
        ":spec" => $record['spec'], ];
    if ($id) {
        $args[":id"] = $id;
        db_query("UPDATE dns.custom_records SET hostname=:hostname, domain=:domain, type=:type, ttl=:ttl, ip=:ip, dyndns=:dyndns, data=:data, spec=:spec WHERE id=:id", $args);
    } else {
        db_query("INSERT INTO dns.custom_records (hostname, domain, type, ttl, ip, dyndns, data, spec) VALUES (:hostname, :domain, :type, :ttl, :ip, :dyndns, :data, :spec)", $args);
    }
}


function delete_dns_record($id)
{
    $id = (int) $id;
    // Diese Funktion prüft, ob der Eintrag einer eigenen Domain gehört
    $record = get_dns_record($id);
    db_query("DELETE FROM dns.custom_records WHERE id=?", [$id]);
}


function convert_from_autorecords($domainid)
{
    $dom = new Domain((int) $domainid);
    $dom->ensure_userdomain();
    $dom = $dom->id;

    db_query("INSERT IGNORE INTO dns.custom_records SELECT r.id, r.lastchange, type, d.id, hostname, ip, NULL AS dyndns, data, spec, ttl FROM dns.v_tmptable_allrecords AS r INNER JOIN dns.v_domains AS d ON (d.name=r.domain) WHERE d.id=?", [$dom]);
    disable_autorecords($dom);
    db_query("UPDATE dns.dnsstatus SET status='outdated'");
    warning("Die automatischen Einträge werden in Kürze abgeschaltet, bitte haben Sie einen Moment Geduld.");
}


function enable_autorecords($domainid)
{
    $dom = new Domain((int) $domainid);
    $dom->ensure_userdomain();
    $dom = $dom->id;

    db_query("UPDATE kundendaten.domains SET autodns=1 WHERE id=?", [$dom]);
    db_query("DELETE FROM dns.custom_records WHERE type='ns' AND domain=? AND hostname IS NULL", [$dom]);
    warning("Die automatischen Einträge werden in Kürze aktiviert, bitte haben Sie einen Moment Geduld.");
}

function disable_autorecords($domainid)
{
    $dom = new Domain((int) $domainid);
    $dom->ensure_userdomain();
    $dom = $dom->id;

    db_query("UPDATE kundendaten.domains SET autodns=0 WHERE id=?", [$dom]);
}


function domain_is_maildomain($domain)
{
    $domain = (int) $domain;
    $result = db_query("SELECT mail FROM kundendaten.domains WHERE id=?", [$domain]);
    $dom = $result->fetch();
    return ($dom['mail'] != 'none');
}


$own_ns = [];

function own_ns()
{
    global $own_ns;

    if (count($own_ns) < 1) {
        $auth = dns_get_record(config('masterdomain'), DNS_NS);
        foreach ($auth as $ns) {
            $own_ns[] = $ns['target'];
        }
    }

    return $own_ns;
}


$tld_ns = [];

function check_dns($domainname, $tld)
{
    global $tld_ns;
    $domain = idn_to_ascii($domainname . "." . $tld, 0, INTL_IDNA_VARIANT_UTS46);

    if (!isset($tld_ns[$tld])) {
        $resp = shell_exec('dig @a.root-servers.net. +noall +authority -t ns ' . $tld . '.');
        $line = explode("\n", $resp, 2)[0];
        $NS = preg_replace("/^.*\\sIN\\s+NS\\s+(\\S+)$/", '\1', $line);
        $tld_ns[$tld] = $NS;
    }

    $resp = shell_exec('dig @' . $tld_ns[$tld] . ' +noall +authority -t ns ' . $domain . '.');
    $line = explode("\n", $resp, 2)[0];
    if (preg_match('/^.*\\sIN\\s+NS\\s+/', $line) === 0) {
        return "NXDOMAIN";
    }
    $NS = preg_replace("/^.*\\sIN\\s+NS\\s+(\\S+).$/", '\1', $line);

    $own_ns = own_ns();

    if (in_array($NS, $own_ns)) {
        return true;
    }
    return $NS;
}

function remove_from_dns($dom)
{
    $domains = get_domain_list($_SESSION['customerinfo']['customerno'], $_SESSION['userinfo']['uid']);
    $current = null;
    foreach ($domains as $d) {
        if ($d->id == $dom && $d->dns == 1) {
            $current = $d;
            break;
        }
    }
    if (!$current) {
        system_failure("Domain nicht gefunden!");
    }
    db_query("UPDATE kundendaten.domains SET dns=0 WHERE id=?", [$current->id]);
}

function add_to_dns($dom)
{
    $domains = get_domain_list($_SESSION['customerinfo']['customerno'], $_SESSION['userinfo']['uid']);
    $current = null;
    foreach ($domains as $d) {
        if ($d->id == $dom && $d->dns == 0) {
            $current = $d;
            break;
        }
    }
    if (!$current) {
        system_failure("Domain nicht gefunden!");
    }
    db_query("UPDATE kundendaten.domains SET dns=1, autodns=1 WHERE id=?", [$current->id]);
}

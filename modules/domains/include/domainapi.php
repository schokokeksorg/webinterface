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

require_once('inc/debug.php');
require_once('inc/api.php');
use_module('contacts');
require_once('contacts.php');
require_once('contactapi.php');



function api_download_domain($id) {
    $result = db_query("SELECT id, CONCAT_WS('.', domainname, tld) AS fqdn, owner, admin_c, registrierungsdatum, kuendigungsdatum FROM kundendaten.domains WHERE id=?", array($id));
    if ($result->rowCount() < 1) {
        system_failure('Domain nicht gefunden');
    }
    $dom = $result->fetch();
    
    $data = array("domainName" => $dom['fqdn']);
    $result = api_request('domainInfo', $data);
    if ($result['status'] != 'success') {
        system_failure("Abfrage nicht erfolgreich!");
    }
    $apidomain = $result['response'];
    $apiowner = NULL;
    $apiadmin_c = NULL;
    foreach ($apidomain['contacts'] as $ac) {
        if ($ac['type'] == 'owner') {
            $apiowner = $ac['contact'];
        }
        if ($ac['type'] == 'admin') {
            $apiadmin_c = $ac['contact'];
        }
    }

    if (! $apiowner || !$apiadmin_c) {
        system_failure("Ungültige Daten erhalten!");
    }
    $owner = download_contact($apiowner);
    $admin_c = $owner;
    if ($apiadmin_c != $apiowner) {
        $admin_c = download_contact($apiadmin_c);
    }
    if ($owner != $dom['owner'] || $admin_c != $dom['admin_c']) {
        db_query("UPDATE kundendaten.domains SET owner=?, admin_c=? WHERE id=?", array($owner, $admin_c, $id));
    }
    return $apidomain;
}


function api_upload_domain($fqdn)
{
    $result = db_query("SELECT id,CONCAT_WS('.', domainname, tld) AS fqdn, owner, admin_c FROM kundendaten.domains WHERE CONCAT_WS('.', domainname, tld)=?", array($fqdn));
    if ($result->rowCount() < 1) {
        system_failure("Unbekannte Domain");
    }
    $dom = $result->fetch();
    $owner = get_contact($dom['owner']);
    if (! $owner['nic_id']) {
        upload_contact($owner);
        $owner = get_contact($dom['owner']);
    }
    $admin_c = get_contact($dom['admin_c']);
    if (! $admin_c['nic_id']) {
        upload_contact($admin_c);
        $admin_c = get_contact($dom['admin_c']);
    }
    $owner = $owner['nic_id'];
    $admin_c = $admin_c['nic_id'];

    $data = array("domainName" => $dom['fqdn']);
    $result = api_request('domainInfo', $data);
    if ($result['status'] != 'success') {
        system_failure("Abfrage nicht erfolgreich!");
    }
    $apidomain = $result['response'];
    foreach ($apidomain['contacts'] as $key => $ac) {
        if ($ac['type'] == 'owner') {
            $apidomain['contacts'][$key]['contact'] = $owner;
        }
        if ($ac['type'] == 'admin') {
            $apidomain['contacts'][$key]['contact'] = $admin_c;
        }
    }
    $args = array("domain" => $apidomain);
    logger(LOG_INFO, "modules/domains/include/domainapi", "domains", "uploading domain »{$fqdn}«");
    $result = api_request('domainUpdate', $args);
    if ($result['status'] == 'error') {
        $msg = $result['errors'][0]['text'];
        logger(LOG_ERR, "modules/domains/include/domainapi", "domains", "ERROR uploading domain »{$fqdn}«: {$msg}");
        system_failure("Es trat ein interner Fehler auf. Bitte dem Support Bescheid geben!");
    }
    return $result;
}


function api_register_domain($domainname, $authinfo=NULL) 
{
    $result = db_query("SELECT id,status,CONCAT_WS('.', domainname, tld) AS fqdn, owner, admin_c FROM kundendaten.domains WHERE CONCAT_WS('.', domainname, tld)=?", array($domainname));
    if ($result->rowCount() < 1) {
        system_failure("Unbekannte Domain");
    }
    $dom = $result->fetch();
    $owner = get_contact($dom['owner']);
    if (! $owner['nic_id']) {
        upload_contact($owner);
        $owner = get_contact($dom['owner']);
    }
    $admin_c = get_contact($dom['admin_c']);
    if (! $admin_c['nic_id']) {
        upload_contact($admin_c);
        $admin_c = get_contact($dom['admin_c']);
    }
    $owner = $owner['nic_id'];
    $admin_c = $admin_c['nic_id'];

    // Frage die Masterdomain ab, von dort übernehmen wir Nameserver und zone/tech handles
    $data = array("domainName" => config('masterdomain'));
    $result = api_request('domainInfo', $data);
    if ($result['status'] != 'success') {
        system_failure("Abfrage nicht erfolgreich!");
    }
    $masterdomain = $result['response'];
    $newdomain = array();
    $newdomain['name'] = $domainname;
    $newdomain['transferLockEnabled'] = true;
    $newdomain['nameservers'] = $masterdomain['nameservers'];
    $newdomain['contacts'] = $masterdomain['contacts'];

    foreach ($masterdomain['contacts'] as $key => $ac) {
        if ($ac['type'] == 'owner') {
            $newdomain['contacts'][$key]['contact'] = $owner;
        }
        if ($ac['type'] == 'admin') {
            $newdomain['contacts'][$key]['contact'] = $admin_c;
        }
    }
    $result = NULL;
    if ($dom['status'] == 'prereg') {
        $args = array("domain" => $newdomain);
        logger(LOG_WARNING, "modules/domains/include/domainapi", "domains", "register new domain »{$newdomain['name']}«");
        $result = api_request('domainCreate', $args);
    } else {
        $args = array("domain" => $newdomain, "transferData" => array("authInfo" => $authinfo));
        logger(LOG_WARNING, "modules/domains/include/domainapi", "domains", "transfer-in domain »{$newdomain['name']}« with authinfo »{$authinfo}«");
        $result = api_request('domainTransfer', $args);
    }
    if ($result['status'] == 'error') {
        $errstr = $result['errors'][0]['text'];
        logger(LOG_ERR, "modules/domains/include/domainapi", "domains", "error registering domain $domainname: {$errstr}");
        system_failure("Es trat ein interner Fehler auf. Bitte dem Support Bescheid geben!");
    }
    return $result;    
}

function api_domain_available($domainname) 
{
    $args = array("domainNames" => array($domainname));
    $result = api_request('domainStatus', $args);
    $resp = $result["responses"][0];
    return $resp;
}


function api_cancel_domain($domainname)
{
    $data = array("domainName" => $domainname);
    $result = api_request('domainInfo', $data);
    if ($result['status'] != 'success') {
        system_failure("Abfrage nicht erfolgreich!");
    }
    $apidomain = $result['response'];
    if (! $apidomain['latestDeletionDateWithoutRenew']) {
        system_failure("Konnte Vertragsende nicht herausfinden.");
    }
    $args = array("domainName" => $domainname, "execDate" => $apidomain['latestDeletionDateWithoutRenew']);
    logger(LOG_WARNING, "modules/domains/include/domainapi", "domains", "cancel domain »{$newdomainname}« at time {$apidomain['latestDeletionDateWithoutRenew']}");
    $result = api_request('domainDelete', $args);
    if ($result['status'] == 'error') {
        $errstr = $result['errors'][0]['text'];
        logger(LOG_ERR, "modules/domains/include/domainapi", "domains", "error canceling domain $domainname: {$errstr}");
        system_failure("Es trat ein interner Fehler auf. Bitte dem Support Bescheid geben!");
    }
    return $result;
 }


function api_unlock_domain($domainname) 
{
    $data = array("domainName" => $domainname);
    $result = api_request('domainInfo', $data);
    if ($result['status'] != 'success') {
        system_failure("Abfrage nicht erfolgreich!");
    }
    $apidomain = $result['response'];
    $apidomain['transferLockEnabled'] = false;
    $args = array("domain" => $apidomain);
    logger(LOG_WARNING, "modules/domains/include/domainapi", "domains", "allow transfer for domain »{$domainname}«");
    $result = api_request('domainUpdate', $args);
    if ($result['status'] == 'error') {
        $errstr = $result['errors'][0]['text'];
        logger(LOG_ERR, "modules/domains/include/domainapi", "domains", "error unlocking domain $domainname: {$errstr}");
        system_failure("Es trat ein interner Fehler auf. Bitte dem Support Bescheid geben!");
    }
    return $result;
}



<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/base.php');
require_once('inc/debug.php');
global $debugmode;
require_once('inc/security.php');

require_role(ROLE_SYSTEMUSER);

require_once('dnsinclude.php');

$section = 'dns_dns';


$record = null;

$id = null;
if ($_REQUEST['id'] == 'new') {
    $record = blank_dns_record($_REQUEST['type']);
} else {
    $id = (int) $_REQUEST['id'];
    $record = get_dns_record($id);
}


if (isset($_GET['action']) && ($_GET['action'] == 'delete')) {
    $sure = user_is_sure();
    if ($sure === null) {
        $domain = new Domain((int) $record['domain']);
        $fqdn = $domain->fqdn;
        if ($record['hostname']) {
            $fqdn = $record['hostname'].'.'.$fqdn;
        }
        are_you_sure("action=delete&id={$id}", "Möchten Sie den ".strtoupper($record['type'])."-Record für ".$fqdn." wirklich löschen?");
    } elseif ($sure === true) {
        delete_dns_record($id);
        if (! $debugmode) {
            header("Location: dns_domain?dom=".$record['domain']);
        }
    } elseif ($sure === false) {
        if (! $debugmode) {
            header("Location: dns_domain?dom=".$record['domain']);
        }
    }
} else {
    // Sicherheitsprüfungen passieren im Backend

    $record['hostname'] = $_REQUEST['hostname'];
    $record['domain'] = (int) $_REQUEST['domain'];
    $record['ip'] = ($_REQUEST['ip'] ?? null);
    $record['data'] = ($_REQUEST['data'] ?? null);
    $record['dyndns'] = (isset($_REQUEST['dyndns']) ? (int) $_REQUEST['dyndns'] : null);
    $record['spec'] = (isset($_REQUEST['spec']) ? (int) $_REQUEST['spec'] : null);
    $record['ttl'] = (int) $_REQUEST['ttl'];

    save_dns_record($id, $record);

    if (!$debugmode) {
        header('Location: dns_domain?dom='.$record['domain']);
    }
}

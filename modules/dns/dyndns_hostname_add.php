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

$section = 'dns_dyndns';

if (!isset($_REQUEST['id'])) {
    system_failure("Keine ID");
}

$id = (int) $_REQUEST['id'];
$dyndns = get_dyndns_account($id);

$type = 'a';
if ($_REQUEST['type'] == 'aaaa') {
    $type = 'aaaa';
}
$record = blank_dns_record($type);
// Sicherheitsprüfungen passieren im Backend

$record['hostname'] = $_REQUEST['hostname'];
$record['domain'] = (int) $_REQUEST['domain'];
$record['dyndns'] = $id;
$record['ttl'] = 120;

save_dns_record(null, $record);

if (!$debugmode) {
    header('Location: dyndns_hostnames?id='.$dyndns['id']);
}

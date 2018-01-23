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

function api_update_domain($id) {
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
}



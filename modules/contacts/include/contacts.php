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
require_role(array(ROLE_CUSTOMER));

/*
require_once('external/http.net/domainRobotApi.php');

function api_fetch_contact($nic_id)
{
  if (! config('http.net-apikey')) {
    system_failure("Kein API-Key vorhanden!");
  }
  $api = new domainRobotApi(config('http.net-apikey'));
}
*/


function new_contact() {
    return array("id" => NULL,
        "state" => NULL,
        "lastchange" => time(),
        "nic_handle" => NULL,
        "nic_id" => NULL,
        "company" => NULL,
        "name" => NULL,
        "address" => NULL,
        "zip" => NULL,
        "city" => NULL,
        "country" => "DE",
        "phone" => NULL,
        "mobile" => NULL,
        "fax" => NULL,
        "email" => NULL,
        "pgp_id" => NULL,
        "pgp_key" => NULL,
        "customer" => $_SESSION['customerinfo']['customerno']);
}


function get_contact($id)
{
    $args = array(
        "cid" => (int) $_SESSION['customerinfo']['customerno'],
        "id" => (int) $id);
    $result = db_query("SELECT id, state, lastchange, nic_id, nic_handle, company, name, address, zip, city, country, phone, mobile, fax, email, pgp_id, pgp_key FROM kundendaten.contacts WHERE id=:id AND customer=:cid", $args);
    if ($result->rowCount() == 0) {
        system_failure("Kontakt nicht gefunden oder gehört nicht diesem Kunden");
    }
    $contact = $result->fetch();
    return $contact;
}

function get_contacts() {
    $cid = (int) $_SESSION['customerinfo']['customerno'];
    $result = db_query("SELECT id, state, lastchange, nic_id, nic_handle, company, name, address, zip, city, country, phone, mobile, fax, email, pgp_id, pgp_key FROM kundendaten.contacts WHERE customer=? ORDER BY id", array($cid));
    $ret = array();
    while ($contact = $result->fetch()) {
        $ret[$contact['id']] = $contact;
    }
    DEBUG($ret);
    return $ret;
}


function get_kundenkontakte() {
    $cid = (int) $_SESSION['customerinfo']['customerno'];
    $result = db_query("SELECT contact_kunde, contact_extern, contact_rechnung FROM kundendaten.kunden WHERE id=?", array($cid));
    $res = $result->fetch();
    $ret = array("kunde" => $res['contact_kunde'],
                 "extern" => $res['contact_extern'],
                 "rechnung" => $res['contact_rechnung']);
    return $ret;
}


function update_pending($contactid) {
    $contactid = (int) $contactid;
    $result = db_query("SELECT email FROM kundendaten.mailaddress_token WHERE contact=?", array($contactid));
    if ($result->rowCount() == 0) {
        return NULL;
    }
    $res = $result->fetch();
    return $res['email'];
}




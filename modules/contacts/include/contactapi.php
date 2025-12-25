<?php

/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('contacts.php');
require_once('inc/debug.php');
require_once('inc/api.php');


function contact_to_apicontact($c)
{
    $ac = [];
    $ac['id'] = $c['nic_id'];
    $ac['handle'] = $c['nic_handle'];
    $ac['type'] = 'person';
    $ac['name'] = $c['name'];
    $ac['organization'] = $c['company'];
    $ac['street'] = explode("\n", $c['address'], 3);
    $ac['postalCode'] = $c['zip'];
    $ac['city'] = $c['city'];
    $ac['country'] = strtolower($c['country']);
    $ac['emailAddress'] = $c['email'];
    $ac['phoneNumber'] = $c['phone'];
    $ac['faxNumber'] = $c['fax'];
    if ($c['state'] == 'deleted') {
        $ac['hidden'] = true;
    }

    return $ac;
}

function apicontact_to_contact($ac)
{
    $c = new_contact();
    $c['nic_id'] = $ac['id'];
    $c['nic_handle'] = $ac['handle'];
    $c['name'] = maybe_null($ac['name']);
    $c['company'] = maybe_null($ac['organization']);
    $c['address'] = implode("\n", $ac['street']);
    $c['zip'] = $ac['postalCode'];
    $c['city'] = $ac['city'];
    $c['country'] = strtoupper($ac['country']);
    $c['email'] = $ac['emailAddress'];
    $c['phone'] = $ac['phoneNumber'];
    $c['fax'] = maybe_null($ac['faxNumber']);
    if ($ac['hidden'] === true) {
        $c['state'] = 'deleted';
    }
    return $c;
}


function missing_properties_for_tld($nic_id, $extension)
{
    $data = ["contactId" => $nic_id,
        "allocation" => "owner",
        "domainSuffixes" => [$extension]];
    $result = api_request('contactUsableFor', $data);
    if ($result['status'] == 'success') {
        if (count($result['response']['missingProperties']) > 0) {
            return $result['response']['missingProperties'];
        }
        return [];
    }
    return null;
}

function download_contact($nic_id)
{
    $data = ["contactId" => $nic_id];
    $result = api_request('contactInfo', $data);
    if ($result['status'] != 'success') {
        system_failure("Abfrage nicht erfolgreich!");
    }
    $c = apicontact_to_contact($result['response']);
    $result = db_query("SELECT id FROM kundendaten.contacts WHERE nic_id=?", [$nic_id]);
    if ($result->rowCount() > 0) {
        $data = $result->fetch();
        $c['id'] = $data['id'];
    }
    $id = save_contact($c);
    save_emailaddress($id, $c['email']);
    return $id;
}


function upload_contact($c)
{
    $ac = contact_to_apicontact($c);
    if ($ac['id'] || $ac['handle']) {
        // Update
        $data = ["contact" => $ac,
            "actingAs" => "designatedAgent", ];
        $result = api_request('contactUpdate', $data);
        if ($result['status'] != 'success') {
            system_failure("Es gab ein Problem beim Hochladen der Adresse zum Domainregistrar. Das sollte nicht sein!");
        }
    } else {
        // create
        $data = ["contact" => $ac];
        $result = api_request('contactCreate', $data);
        if ($result['status'] == 'success') {
            $c['nic_handle'] = $result['response']['handle'];
            $c['nic_id'] = $result['response']['id'];
            save_contact($c);
        }
    }
}

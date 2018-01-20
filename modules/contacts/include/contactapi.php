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

require_once('contacts.php');
require_once('inc/debug.php');
require_once('inc/api.php');


function contact_to_apicontact($c) 
{
    $ac = array();
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


function upload_contact($c)
{
    $ac = contact_to_apicontact($c);
    if ($ac['id'] || $ac['handle']) {
        // Update
        $data = array("contact" => $ac);
        $result = api_request('contactUpdate', $data);
        if ($result['status'] != 'success') {
            system_failure("Es gab ein Problem beim Hochladen der Adresse zum Domainregistrar. Das sollte nicht sein!");
        }
    } else {
        // create
        $data = array("contact" => $ac);
        $result = api_request('contactCreate', $data);
        if ($result['status'] == 'success') {
            $c['nic_handle'] = $result['response']['handle'];
            $c['nic_id'] = $result['response']['id'];
            save_contact($c);
        }
    }
}



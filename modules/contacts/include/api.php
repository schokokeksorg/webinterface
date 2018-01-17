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

function api_request($method, $input_data) 
{
    $url = config('http.net-apiurl').'domain/v1/json/'.$method;
    $input_data['authToken'] = config('http.net-apikey');
    DEBUG('======= API REQUEST ==========');
    DEBUG($url);
    DEBUG($input_data);
    $curl = curl_init($url);
    $json = json_encode($input_data);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 
    $result = curl_exec($curl);
    if ($result === FALSE) {
        system_failure("API-Anfrage kaputt");
    }
    DEBUG('==============================');
    DEBUG($result);
    $output_data = json_decode($result, true);
    DEBUG($output_data);
    return $output_data;
}



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

    return $ac;
}


function upload_contact($c)
{
    $ac = contact_to_apicontact($c);
    if ($ac['id'] || $ac['handle']) {
        // Update
        $data = array("contact" => $ac);
        $result = api_request('contactUpdate', $data);
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



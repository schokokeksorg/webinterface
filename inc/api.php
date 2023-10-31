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

function api_request($method, $input_data)
{
    $url = config('http.net-apiurl') . 'domain/v1/json/' . $method;
    $input_data['authToken'] = config('http.net-apikey');
    DEBUG('======= API REQUEST ==========');
    DEBUG($url);
    DEBUG($input_data);
    $curl = curl_init($url);
    $json = json_encode($input_data);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $result = curl_exec($curl);
    if ($result === false) {
        system_failure("API-Anfrage kaputt");
    }
    DEBUG('==============================');
    DEBUG($result);
    $output_data = json_decode($result, true);
    DEBUG($output_data);
    return $output_data;
}

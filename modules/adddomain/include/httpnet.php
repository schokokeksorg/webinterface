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

require_once('external/http.net/domainRobotApi.php');

require_once('inc/debug.php');
require_once('inc/base.php');
require_once('inc/security.php');
require_once('inc/error.php');

$url = 'https://partner.http.net/api/domain/v1/json/';
$available_methods = array("domainStatus","domainUpdate");

function httpnet_request($method, $data) {
  if (! in_array($method, $avalable_methods)) {
    system_failure("invalid API method: $method");
  }
  //$data
}



function terions_available($domainname) 
{
  if (! config('http.net-apikey')) {
    system_failure("Kein API-Key vorhanden!");
  }
  $api = new domainRobotApi(config('http.net-apikey'));
  $result = $api->domainStatus($domainname);
  if (isset($api->getValue()[0])) {
    return ($api->getValue()[0]->status == 'available');
  }
  return false;
}



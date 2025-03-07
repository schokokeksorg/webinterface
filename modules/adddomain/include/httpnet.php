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
require_once('inc/api.php');


function api_domain_available($domainname)
{
    $args = ["domainNames" => [$domainname]];
    $result = api_request('domainStatus', $args);
    $resp = $result["responses"][0];
    return ($resp["status"] == "available");
}

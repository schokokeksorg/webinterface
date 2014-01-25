<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2013 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/debug.php');
require_once('inc/base.php');
require_once('inc/security.php');
require_once('inc/error.php');

require_once('terions.php');


function terions_available($domainname) 
{
  $result = terions_send_request('info', 'check_status', array('domain' => $domainname));
  $val = $result->value();
  DEBUG($val);
  if (strstr($val->scalarval(), 'Domain not taken.')) {
    return true;
  }
  return false;
}


function terions_send_request($action, $task, $values)
{
  $conf = get_xmlrpc_config();
  require_once('external/xmlrpc/xmlrpc.inc');

  $client=new xmlrpc_client("/RX", "www.regspeed.de", 443);

  $xmlvalues = array(
    "username" => new xmlrpcval($conf['username']),
    "password" => new xmlrpcval($conf['password']),
    "key" => new xmlrpcval($conf['key']),
    "task" => new xmlrpcval($task)
    );
  foreach ($values as $key => $val) {
    if ($val === (int) $val) {
      DEBUG('INT: '.$val);
      $xmlvalues[$key] = new xmlrpcval((int) $val, 'int');
    } else {
      $xmlvalues[$key] = new xmlrpcval($val);
    }
  }
  $data = new xmlrpcval($xmlvalues, "struct");
  
  $cmd = new xmlrpcmsg($action,array($data));
  $client->setSSLVerifyHost(2);
  $response = $client->send($cmd,'0', 'https');
  DEBUG($response);
  return $response;
}


function get_xmlrpc_config() 
{
  $conf = array(
    "username" => config('terions_username'),
    "password" => config('terions_password'),
    "key" => config('terions_xmlrpckey')
    );
  if (! $conf['username'] || ! $conf['password'] || ! $conf['key']) {
    system_failure('XML-RPC-Zugangsdaten nicht vorhanden');
  }
  return $conf;
}



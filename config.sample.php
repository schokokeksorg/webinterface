<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

global $config;
$config = [];

// either...
$config['db_socket'] = '/var/run/mysqld/mysqld-sys.sock';
// ... or
$config['db_host'] = '10.8.0.1';
$config['db_port'] = 3307;
// (socket is preferred if both are defined)

$config['db_user'] = 'username';
$config['db_pass'] = 'password';


$config['modules'] = ["index", "domains", "imap", "mysql", "jabber", "vhosts", "register", "systemuser", "su"];

$config['enable_debug'] = true;
$config['logging'] = LOG_ERR;


$config['mime_type'] = 'text/html';

$config['session_name'] = 'CONFIG_SCHOKOKEKS_ORG';
$config['theme'] = 'default';
$config['jquery_ui_path'] = '/external/jquery';

ini_set('display_errors', 'On');

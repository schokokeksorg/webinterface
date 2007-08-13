<?php

global $config;
$config = array();

$config['db_host'] = ':/var/run/mysqld/mysqld-sys.sock';
$config['db_user'] = 'username';
$config['db_pass'] = 'password';


$config['modules'] = array("index", "domains", "imap", "mysql", "jabber", "vhosts", "register", "systemuser", "su");

$config['use_cracklib'] = true;
$config['cracklib_dict'] = 'inc/cracklib_dict';

$config['enable_debug'] = true;


ini_set('error_reporting','On');

?>

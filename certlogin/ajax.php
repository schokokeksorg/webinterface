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

// Setze das Arbeitsverzeichnis auf das Stammverzeichnis, damit die Voraussetzungen gleich sind wie bei allen anderen Requests
chdir('..');

require_once('config.php');
global $prefix;
$prefix = '../';

// Das Parent-Verzeichnis in den Include-Pfad, da wir uns jetzt in einem anderen Verzeichnis befinden.
ini_set('include_path', ini_get('include_path').':../');

require_once('session/start.php');
require_once('inc/base.php');
require_once('inc/debug.php');
require_once('inc/error.php');
require_once('inc/theme.php');


function prepare_cert($cert)
{
	return str_replace(array('-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', ' ', "\n"), array(), $cert);
}


function get_logins_by_cert($cert) 
{
	$cert = db_escape_string(prepare_cert($cert));
	$query = "SELECT type,username,startpage FROM system.clientcert WHERE cert='{$cert}'";
	$result = db_query($query);
	if ($result->rowCount() < 1)
		return NULL;
	else {
		$ret = array();
		while ($row = $result->fetch()) {
			$ret[] = $row;
		}
		return $ret;
	}
}

DEBUG('$_SERVER:');
DEBUG($_SERVER);

header('Content-type: text/plain');

if (isset($_SERVER['REDIRECT_SSL_CLIENT_CERT']) && 
    isset($_SERVER['REDIRECT_SSL_CLIENT_S_DN']) && $_SERVER['REDIRECT_SSL_CLIENT_S_DN'] != '' && 
    isset($_SERVER['REDIRECT_SSL_CLIENT_I_DN']) && $_SERVER['REDIRECT_SSL_CLIENT_I_DN'] != '') {
  $ret = get_logins_by_cert($_SERVER['REDIRECT_SSL_CLIENT_CERT']);
  if ($ret === NULL) {
    echo 'error';
    die();
  }
  if (count($ret) == 1) {
    $uid = $ret[0]['username'];
    $role = find_role($uid, '', True);
    setup_session($role, $uid);
    setcookie('CLIENTCERT_AUTOLOGIN', '1', time()+3600*24*365, '/');
    echo 'ok';
    die();
  }
}
echo 'error';
die();
?>

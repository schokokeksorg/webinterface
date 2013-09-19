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

require_once('inc/security.php');

function do_ajax_cert_login() {
  global $prefix;
  $path = config('jquery_ui_path');
  html_header('
<link rel="stylesheet" href="'.$path.'/themes/base/jquery-ui.css" />
<script type="text/javascript" src="'.$path.'/jquery-1.9.0.js" ></script>
<script type="text/javascript" src="'.$path.'/ui/jquery-ui.js" ></script>
<script type="text/javascript">
  function redirect(status) {
    if (status == "ok") {
      window.location.reload();
    } else {
      window.location.href="../../certlogin/";
    }
  }
  $.get("'.$prefix.'certlogin/ajax.php", redirect);
</script>
');
}

function get_logins_by_cert($cert) 
{
	$cert = DB::escape(str_replace(array('-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', ' ', "\n"), array(), $cert));
	$query = "SELECT type,username,startpage FROM system.clientcert WHERE cert='{$cert}'";
	$result = DB::query($query);
	if ($result->num_rows < 1)
		return NULL;
	else {
		$ret = array();
		while ($row = $result->fetch_assoc()) {
			$ret[] = $row;
		}
		return $ret;
	}
}

function get_cert_by_id($id) 
{
  $id = (int) $id;
	if ($id == 0)
	  system_failure('no ID');
	$query = "SELECT id,dn,issuer,cert,username,startpage FROM system.clientcert WHERE `id`='{$id}' LIMIT 1";
	$result = DB::query($query);
	if ($result->num_rows < 1)
		return NULL;
	$ret = $result->fetch_assoc();
  DEBUG($ret);
  return $ret;
}


function get_certs_by_username($username) 
{
	$username = DB::escape($username);
	if ($username == '')
	  system_failure('empty username');
	$query = "SELECT id,dn,issuer,cert,startpage FROM system.clientcert WHERE `username`='{$username}'";
	$result = DB::query($query);
	if ($result->num_rows < 1)
		return NULL;
	while ($row = $result->fetch_assoc()) {
	  $ret[] = $row;
	}
	return $ret;
}


function add_clientcert($certdata, $dn, $issuer, $startpage='')
{
  $type = NULL;
  $username = NULL;
  if ($_SESSION['role'] & ROLE_SYSTEMUSER) {
    $type = 'user';
    $username = DB::escape($_SESSION['userinfo']['username']);
    if (isset($_SESSION['subuser'])) {
      $username = DB::escape($_SESSION['subuser']);
      $type = 'subuser';
    }
  } elseif ($_SESSION['role'] & ROLE_VMAIL_ACCOUNT) {
    $type = 'email';
    $username = DB::escape($_SESSION['mailaccount']);
  }
  if (! $type || ! $username) {
    system_failure('cannot get type or username of login');
  }
  $certdata = DB::escape($certdata);
  $dn = maybe_null(DB::escape($dn));
  $issuer = maybe_null(DB::escape($issuer));
  if ($startpage &&  ! check_path($startpage))
    system_failure('Startseite kaputt');
  $startpage = maybe_null(DB::escape($startpage));

  if ($certdata == '')
    system_failure('Kein Zertifikat');
  DEBUG($certdata);
  DEBUG($dn);
  DEBUG($issuer);

  DB::query("INSERT INTO system.clientcert (`dn`, `issuer`, `cert`, `type`, `username`, `startpage`) 
VALUES ({$dn}, {$issuer}, '{$certdata}', '{$type}', '{$username}', {$startpage})");

}


function delete_clientcert($id)
{
  $id = (int) $id;
  $type = NULL;
  $username = NULL;
  if ($_SESSION['role'] & ROLE_SYSTEMUSER) {
    $type = 'user';
    $username = DB::escape($_SESSION['userinfo']['username']);
    if (isset($_SESSION['subuser'])) {
      $username = DB::escape($_SESSION['subuser']);
      $type = 'subuser';
    }
  } elseif ($_SESSION['role'] & ROLE_VMAIL_ACCOUNT) {
    $type = 'email';
    $username = DB::escape($_SESSION['mailaccount']);
  }
  if (! $type || ! $username) {
    system_failure('cannot get type or username of login');
  }
  DB::query("DELETE FROM system.clientcert WHERE id={$id} AND type='{$type}' AND username='{$username}' LIMIT 1");
}


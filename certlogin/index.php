<?php

require_once('../config.php');
global $prefix;
$prefix = '../';

// Das Parent-Verzeichnis in den Include-Pfad, da wir uns jetzt in einem anderen Verzeichnis befinden.
ini_set('include_path', ini_get('include_path').':../');

require_once('session/start.php');
require_once('inc/base.php');
require_once('inc/debug.php');
require_once('inc/error.php');


function prepare_cert($cert)
{
	return str_replace(array('-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', ' ', "\n"), array(), $cert);
}


function get_logins_by_cert($cert) 
{
	$cert = mysql_real_escape_string(prepare_cert($cert));
	$query = "SELECT type,username,startpage FROM system.clientcert WHERE cert='{$cert}'";
	$result = db_query($query);
	if (mysql_num_rows($result) < 1)
		return NULL;
	else {
		$ret = array();
		while ($row = mysql_fetch_assoc($result)) {
			$ret[] = $row;
		}
		return $ret;
	}
}

DEBUG($_ENV);

if ($_SESSION['role'] != ROLE_ANONYMOUS && isset($_REQUEST['record']) && isset($_REQUEST['backto']) && check_path($_REQUEST['backto']))
{
  DEBUG('recording client-cert');
  if (isset($_ENV['REDIRECT_SSL_CLIENT_CERT']) && $_ENV['REDIRECT_SSL_CLIENT_S_DN'] != '' && $_ENV['REDIRECT_SSL_CLIENT_I_DN'] != '')
  {
    $_SESSION['clientcert_cert'] = prepare_cert($_ENV['REDIRECT_SSL_CLIENT_CERT']);
    $_SESSION['clientcert_dn'] = $_ENV['REDIRECT_SSL_CLIENT_S_DN'];
    $_SESSION['clientcert_issuer'] = $_ENV['REDIRECT_SSL_CLIENT_I_DN'];
    header('Location: '.$prefix.$_REQUEST['backto'].encode_querystring(''));
    die();
  }
  else
  {
    system_failure('Ihr Browser hat kein Client-Zertifikat gesendet');
  }
}
elseif (isset($_REQUEST['type']) && isset($_REQUEST['username'])) {
  if (!isset($_ENV['REDIRECT_SSL_CLIENT_CERT'])) 
    system_failure('Ihr Browser hat kein Client-Zertifikat gesendet');

  $ret = get_logins_by_cert($_ENV['REDIRECT_SSL_CLIENT_CERT']);
  foreach ($ret as $account) {
    if (($account['type'] == $_REQUEST['type']) && ($account['username'] == $_REQUEST['username'])) {
      $uid = $account['username'];
      $role = find_role($uid, '', True);
      setup_session($role, $uid);
      $destination = 'go/index/index';
      if (check_path($account['startpage']))
        $destination = $account['startpage'];
      if (isset($_REQUEST['destination']) && check_path($_REQUEST['destination']))
        $destination = $_REQUEST['destination'];
      header('Location: ../'.$destination);
      die();
    }
  }
  system_failure('Der angegebene Account kann mit diesem Client-Zertifikat nicht eingeloggt werden.');
}
else
{
  if (isset($_ENV['REDIRECT_SSL_CLIENT_CERT']) && $_ENV['REDIRECT_SSL_CLIENT_S_DN'] != '' && $_ENV['REDIRECT_SSL_CLIENT_I_DN'] != '') {
    $ret = get_logins_by_cert($_ENV['REDIRECT_SSL_CLIENT_CERT']);
    if ($ret === NULL) {
      system_failure('Ihr Browser hat ein Client-Zertifikat gesendet, dieses ist aber noch nicht für den Zugang hinterlegt. Gehen Sie bitte zurück und melden Sie sich bitte per Benutzername und Passwort an.');
    }
    if (count($ret) == 1) {
      $uid = $ret[0]['username'];
      $role = find_role($uid, '', True);
      setup_session($role, $uid);
      $destination = 'go/index/index';
      if (check_path($ret[0]['startpage']))
        $destination = $ret[0]['startpage'];
      if (isset($_REQUEST['destination']) && check_path($_REQUEST['destination']))
        $destination = $_REQUEST['destination'];
      header('Location: ../'.$destination);
      die();
    }
    output('<p>Ihr Browser hat ein gültiges SSL-Client-Zertifikat gesendet, mit dem Sie sich auf dieser Seite einloggen können. Allerdings haben Sie dieses Client-Zertifikat für mehrere Zugänge hinterlegt. Wählen Sie bitte den Zugang aus, mit dem Sie sich anmelden möchten.</p>
      <ul>');
    foreach ($ret as $account) {
      $type = 'System-Account';
      if ($account['type'] == 'email') {
        $type = 'E-Mail-Konto';
      }
      elseif ($account['type'] == 'customer') {
        $type = 'Kundenaccount';
      }
      output('<li>'.internal_link('', $type.': <strong>'.$account['username'].'</strong>', 'type='.$account['type'].'&username='.urlencode($account['username']).'&destination='.urlencode($destination)).'</li>');
    }
    output('</ul>');
  } else {
    system_failure('Ihr Browser hat kein Client-Zertifikat gesendet.');
  }
}

?>

<?php

require_once('inc/base.php');

define("CERT_OK", 0);
define("CERT_INVALID", 1);
define("CERT_NOCHAIN", 2);

function user_certs()
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT id, valid_from, valid_until, subject, cn FROM vhosts.certs WHERE uid=${uid}");
  $ret = array();
  while ($i = mysql_fetch_assoc($result))
    $ret[] = $i;
  DEBUG($ret);
  return $ret;
}


function cert_details($id)
{
  $id = (int) $id;
  $uid = (int) $_SESSION['userinfo']['uid'];
  
  $result = db_query("SELECT id, lastchange, valid_from, valid_until, subject, cn, cert, `key`, cabundle FROM vhosts.certs WHERE uid={$uid} AND id={$id}");
  if (mysql_num_rows($result) != 1)
    system_failure("Ungültiges Zertifikat");
  return mysql_fetch_assoc($result);
}


function get_available_CAs()
{
  $path = '/etc/apache2/certs/cabundle/';
  $ret = glob($path.'*.pem');
  if (! $ret)
    system_failure("Konnte die CA-Zertifikate nicht laden");
  DEBUG($ret);
  return $ret;
}


function validate_certificate($cert, $key)
{  
  if (openssl_x509_check_private_key($cert, $key) !== true)
  {
    DEBUG("Zertifikat und Key passen nicht zusammen");
    return CERT_INVALID;
  }

  $cacerts = get_available_CAs();

  if (openssl_x509_checkpurpose($cert, X509_PURPOSE_SSL_SERVER, $cacerts) !== true)
  { 
    DEBUG('certificate was not validated as a server certificate with the available chain');
    return CERT_NOCHAIN;
  }

  return CERT_OK;
}


function parse_cert_details($cert)
{
  $certdata = openssl_x509_parse($cert, true);
  /* 
name => /CN=*.bwurst.org
validFrom_time_t => 1204118790
validTo_time_t => 1267190790


  */
 
  return array('subject' => $certdata['name'], 'cn' => $certdata['subject']['CN'], 'valid_from' => date('Y-m-d', $certdata['validFrom_time_t']), 'valid_until' => date('Y-m-d', $certdata['validTo_time_t']));
}


function save_cert($info, $cert, $key)
{
  $subject = mysql_real_escape_string(filter_input_general($info['subject']));
  $cn = mysql_real_escape_string(filter_input_general($info['cn']));
  $valid_from = mysql_real_escape_string($info['valid_from']);
  $valid_until = mysql_real_escape_string($info['valid_until']);
  $cert = mysql_real_escape_string($cert);
  $key = mysql_real_escape_string($key);
  $uid = (int) $_SESSION['userinfo']['uid'];

  db_query("INSERT INTO vhosts.certs (uid, subject, cn, valid_from, valid_until, cert, `key`) VALUES ({$uid}, '{$subject}', '{$cn}', '{$valid_from}', '{$valid_until}', '{$cert}', '{$key}')");
}


function delete_cert($id)
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $id = (int) $id;
  
  db_query("DELETE FROM vhosts.certs WHERE uid={$uid} AND id={$id} LIMIT 1");
}


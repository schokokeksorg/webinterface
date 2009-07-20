<?php

require_once('inc/base.php');
require_once('inc/security.php');

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

function user_csr()
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT id, created, hostname, bits FROM vhosts.csr WHERE uid=${uid}");
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
  
  $result = db_query("SELECT id, lastchange, valid_from, valid_until, subject, cn, cert, `key` FROM vhosts.certs WHERE uid={$uid} AND id={$id}");
  if (mysql_num_rows($result) != 1)
    system_failure("Ungültiges Zertifikat");
  return mysql_fetch_assoc($result);
}


function csr_details($id)
{
  $id = (int) $id;
  $uid = (int) $_SESSION['userinfo']['uid'];
  
  $result = db_query("SELECT id, created, hostname, bits, csr, `key` FROM vhosts.csr WHERE uid={$uid} AND id={$id}");
  if (mysql_num_rows($result) != 1)
    system_failure("Ungültiger CSR");
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

function delete_csr($id)
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $id = (int) $id;
  
  db_query("DELETE FROM vhosts.csr WHERE uid={$uid} AND id={$id} LIMIT 1");
}

function create_wildcard_csr($cn, $bits)
{
  $cn = filter_input_hostname($cn);
  $bits = (int) $bits;
  if ($bits == 0)
    $bits = 4096;

  $keyfile = tempnam(ini_get('upload_tmp_dir'), 'key');
  $csrfile = tempnam(ini_get('upload_tmp_dir'), 'csr');
  $config = tempnam(ini_get('upload_tmp_dir'), 'config');

  DEBUG("key: ".$keyfile." / csr: ".$csrfile." / config: ".$config);

  $c = fopen($config, "w");
  fwrite($c, "[req]
default_bits = {$bits}
default_keyfile = {$keyfile}
encrypt_key = no
distinguished_name      = req_distinguished_name
req_extensions = v3_req

[v3_req]
subjectAltName = DNS:{$cn}, DNS:*.{$cn}

[ req_distinguished_name ]
countryName                     = Country Name (2 letter code)
countryName_default             = DE
stateOrProvinceName             = State or Province Name (full name)
stateOrProvinceName_default     = Baden-Wuerttemberg
localityName                    = Locality Name (eg, city)
localityName_default            = Murrhardt
0.organizationName              = Organization Name (eg, company)
0.organizationName_default      = schokokeks.org

commonName = Common Name
commonName_default = *.{$cn}
");
  fclose($c);

  $output = '';
  $cmdline = "openssl req -new -batch -config {$config} -out {$csrfile}";
  $retval = 0;
  exec($cmdline, $output, $retval);
  DEBUG($output);
  DEBUG($retval);
  if ($retval != 0)
  {
    system_failure("Die Erzeugung des CSR ist fehlgeschlagen. Ausgabe des OpenSSL-Befehls: ".$output);
  }
  
  $csr = file_get_contents($csrfile);
  $key = file_get_contents($keyfile);

  unlink($csrfile);
  unlink($keyfile);
  unlink($config);

  return array($csr, $key);
}



function create_csr($cn, $bits)
{
  $cn = filter_input_hostname($cn);
  $bits = (int) $bits;
  if ($bits == 0)
    $bits = 4096;

  $keyfile = tempnam(ini_get('upload_tmp_dir'), 'key');
  $csrfile = tempnam(ini_get('upload_tmp_dir'), 'csr');
  $config = tempnam(ini_get('upload_tmp_dir'), 'config');

  DEBUG("key: ".$keyfile." / csr: ".$csrfile." / config: ".$config);

  $c = fopen($config, "w");
  fwrite($c, "[req]
default_bits = {$bits}
default_keyfile = {$keyfile}
encrypt_key = no
distinguished_name      = req_distinguished_name

[ req_distinguished_name ]
countryName                     = Country Name (2 letter code)
countryName_default             = DE
stateOrProvinceName             = State or Province Name (full name)
stateOrProvinceName_default     = Baden-Wuerttemberg
localityName                    = Locality Name (eg, city)
localityName_default            = Murrhardt
0.organizationName              = Organization Name (eg, company)
0.organizationName_default      = schokokeks.org

commonName = Common Name
commonName_default = {$cn}
");
  fclose($c);

  $output = '';
  $cmdline = "openssl req -new -batch -config {$config} -out {$csrfile}";
  $retval = 0;
  exec($cmdline, $output, $retval);
  DEBUG($output);
  DEBUG($retval);
  if ($retval != 0)
  {
    system_failure("Die Erzeugung des CSR ist fehlgeschlagen. Ausgabe des OpenSSL-Befehls: ".$output);
  }
  
  $csr = file_get_contents($csrfile);
  $key = file_get_contents($keyfile);

  unlink($csrfile);
  unlink($keyfile);
  unlink($config);

  return array($csr, $key);
}



function save_csr($cn, $bits, $wildcard=true)
{
  $csr = NULL;
  $key = NULL;
  if ($wildcard)
    list($csr, $key) = create_wildcard_csr($cn, $bits);
  else
    list($csr, $key) = create_csr($cn, $bits);
  
  $uid = (int) $_SESSION['userinfo']['uid'];
  $cn = mysql_real_escape_string(filter_input_hostname($cn));
  $bits = (int) $bits;
  $csr = mysql_real_escape_string($csr);
  $key = mysql_real_escape_string($key);
  db_query("INSERT INTO vhosts.csr (uid, hostname, bits, csr, `key`) VALUES ({$uid}, '{$cn}', {$bits}, '{$csr}', '{$key}')");
  $id = mysql_insert_id();
  return $id;  
}



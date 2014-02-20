<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2014 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/base.php');
require_once('inc/security.php');

define("CERT_OK", 0);
define("CERT_INVALID", 1);
define("CERT_NOCHAIN", 2);

function user_certs()
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT id, valid_from, valid_until, subject, cn FROM vhosts.certs WHERE uid=? ORDER BY cn", array($uid));
  $ret = array();
  while ($i = $result->fetch())
    $ret[] = $i;
  DEBUG($ret);
  return $ret;
}

function user_csr()
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT id, created, hostname, bits FROM vhosts.csr WHERE uid=? ORDER BY hostname", array($uid));
  $ret = array();
  while ($i = $result->fetch())
    $ret[] = $i;
  DEBUG($ret);
  return $ret;
}

function cert_details($id)
{
  $id = (int) $id;
  $uid = (int) $_SESSION['userinfo']['uid'];
  
  $result = db_query("SELECT id, lastchange, valid_from, valid_until, subject, cn, cert, `key` FROM vhosts.certs WHERE uid=:uid AND id=:id", array(":uid" => $uid, ":id" => $id));
  if ($result->rowCount() != 1)
    system_failure("Ungültiges Zertifikat #{$id}");
  return $result->fetch();
}


function csr_details($id)
{
  $id = (int) $id;
  $uid = (int) $_SESSION['userinfo']['uid'];
  
  $result = db_query("SELECT id, created, hostname, bits, `replace`, csr, `key` FROM vhosts.csr WHERE uid=:uid AND id=:id", array(":uid" => $uid, ":id" => $id));
  if ($result->rowCount() != 1)
    system_failure("Ungültiger CSR");
  return $result->fetch();
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


function get_chain($cert)
{
  $certdata = openssl_x509_parse($cert, true);
  if ($certdata === FALSE) {
    system_failure("Das Zertifikat konnte nicht gelesen werden");
  }
  if (! isset($certdata['issuer']['CN'])) {
    return NULL;
  }
  $result = db_query("SELECT id FROM vhosts.certchain WHERE cn=?", array($certdata['issuer']['CN']));
  if ($result->rowCount() > 0)
  {
    $c = $result->fetch();
    //$chainfile = '/etc/apache2/certs/chains/'.$c['id'].'.pem';
    DEBUG("identified fitting certificate chain #".$c['id']);
    return $c['id'];
  }
}


function validate_certificate($cert, $key)
{ 
  // Lade private key 
  $seckey = openssl_get_privatekey($key);
  if ($seckey === FALSE) {
    system_failure("Der private Schlüssel konnte (ohne Passwort) nicht gelesen werden.");
  }
  // Lade public key
  $pubkey = openssl_get_publickey($cert);
  if ($pubkey === FALSE) {
    system_failure("In dem eingetragenen Zertifikat wurde kein öffentlicher Schlüssel gefunden.");
  }
  // Parse Details über den pubkey
  $certinfo = openssl_pkey_get_details($pubkey);
  DEBUG($certinfo);
  if ($certinfo === FALSE) {
    system_failure("Der öffentliche Schlüssel des Zertifikats konnte nicht gelesen werden");
  }

  // Apache unterstützt nur Schlüssel vom Typ RSA oder DSA
  if (! in_array($certinfo['type'], array(OPENSSL_KEYTYPE_RSA, OPENSSL_KEYTYPE_DSA))) {
    system_failure("Dieser Schlüssel nutzt einen nicht unterstützten Algorithmus.");
  }
    
  // Bei ECC-Keys treten kürzere Schlüssellängen auf, die können wir aktuell aber sowieso nicht unterstützen
  if ($certinfo['bits'] < 2048) {
    warning("Dieser Schlüssel hat eine sehr geringe Bitlänge und ist daher als nicht besonders sicher einzustufen!");
  }

  // Prüfe ob Key und Zertifikat zusammen passen
  if (openssl_x509_check_private_key($cert, $key) !== true)
  {
    DEBUG("Zertifikat und Key passen nicht zusammen: ".openssl_x509_check_private_key($cert, $key));
    return CERT_INVALID;
  }

  $cacerts = array('/etc/ssl/certs');
  $chain = (int) get_chain($cert);
  if ($chain)
  {
    $result = db_query("SELECT content FROM vhosts.certchain WHERE id=?", array($chain));
    $tmp = $result->fetch();
    $chaincert = $tmp['content'];
    $chainfile = tempnam(sys_get_temp_dir(), 'webinterface');
    $f = fopen($chainfile, "w");
    fwrite($f, $chaincert);
    fclose($f);
    $cacerts[] = $chainfile;
  }

  $valid = openssl_x509_checkpurpose($cert, X509_PURPOSE_SSL_SERVER, $cacerts);
  if ($chain) {
    unlink($chainfile);
  }
  if ($valid !== true)
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
  DEBUG($certdata);
  //return array('subject' => $certdata['name'], 'cn' => $certdata['subject']['CN'], 'valid_from' => date('Y-m-d', $certdata['validFrom_time_t']), 'valid_until' => date('Y-m-d', $certdata['validTo_time_t']));
  return array('subject' => $certdata['subject']['CN'].' / '.$certdata['issuer']['O'], 'cn' => $certdata['subject']['CN'], 'valid_from' => date('Y-m-d', $certdata['validFrom_time_t']), 'valid_until' => date('Y-m-d', $certdata['validTo_time_t']), 'issuer' => $certdata['issuer']['CN']);
}


function save_cert($info, $cert, $key)
{
  openssl_pkey_export($key, $key);
  openssl_x509_export($cert, $cert);
  $uid = (int) $_SESSION['userinfo']['uid'];

  db_query("INSERT INTO vhosts.certs (uid, subject, cn, valid_from, valid_until, chain, cert, `key`) VALUES (:uid, :subject, :cn, :valid_from, :valid_until, :chain, :cert, :key)", 
        array(":uid" => $uid, ":subject" => filter_input_general($info['subject']), ":cn" => filter_input_general($info['cn']), ":valid_from" => $info['valid_from'], 
              ":valid_until" => $info['valid_until'], ":chain" => get_chain($cert), ":cert" => $cert, ":key" => $key));
}


function refresh_cert($id, $info, $cert, $key = NULL)
{
  openssl_x509_export($cert, $cert);
  $chain = get_chain($cert);

  $id = (int) $id;
  $oldcert = cert_details($id);
  $args = array(":subject" => filter_input_general($info['subject']),
                ":cn" => filter_input_general($info['cn']),
                ":cert" => $cert,
                ":valid_from" => $info['valid_from'],
                ":valid_until" => $info['valid_until'],
                ":chain" => get_chain($cert),
                ":id" => $id);

  $keyop = '';
  if ($key) {
    openssl_pkey_export($key, $key);
    $keyop = ", `key`=:key";
    $args[":key"] = $key;
  }
  db_query("UPDATE vhosts.certs SET subject=:subject, cn=:cn, cert=:cert{$keyop}, valid_from=:valid_from, valid_until=:valid_until, chain=:chain WHERE id=:id", $args);
}


function delete_cert($id)
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $id = (int) $id;
  
  db_query("DELETE FROM vhosts.certs WHERE uid=? AND id=?", array($uid, $id));
}

function delete_csr($id)
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $id = (int) $id;
  
  db_query("DELETE FROM vhosts.csr WHERE uid=? AND id=?", array($uid, $id));
}


function create_csr($cn, $bits)
{
  $cn = filter_input_hostname($cn, true);
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
countryName_default             = 
stateOrProvinceName             = State or Province Name (full name)
stateOrProvinceName_default     = 
localityName                    = Locality Name (eg, city)
localityName_default            = 
0.organizationName              = Organization Name (eg, company)
0.organizationName_default      = 

commonName = Common Name
commonName_default = {$cn}
");
  fclose($c);

  $output = '';
  $cmdline = "openssl req -sha256 -new -batch -config {$config} -out {$csrfile}";
  $retval = 0;
  exec($cmdline, $output, $retval);
  DEBUG($output);
  DEBUG($retval);
  if ($retval != 0)
  {
    system_failure("Die Erzeugung des CSR ist fehlgeschlagen. Ausgabe des OpenSSL-Befehls: ".print_r($output, true));
  }
  
  $csr = file_get_contents($csrfile);
  $key = file_get_contents($keyfile);

  unlink($csrfile);
  unlink($keyfile);
  unlink($config);

  return array($csr, $key);
}



function save_csr($cn, $bits, $replace=NULL)
{
  if (! $cn) {
    system_failure("Sie müssen einen Domainname eingeben!");
  }
  $csr = NULL;
  $key = NULL;
  list($csr, $key) = create_csr($cn, $bits);
  
  $uid = (int) $_SESSION['userinfo']['uid'];
  db_query("INSERT INTO vhosts.csr (uid, hostname, bits, `replace`, csr, `key`) VALUES (:uid, :cn, :bits, :replace, :csr, :key)",
           array(":uid" => $uid, ":cn" => filter_input_hostname($cn, true), ":bits" => $bits, 
                 ":replace" => $replace, ":csr" => $csr, ":key" => $key));
  $id = db_insert_id();
  return $id;  
}



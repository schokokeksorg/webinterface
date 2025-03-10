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
require_once('inc/security.php');

define("CERT_OK", 0);
define("CERT_INVALID", 1);
define("CERT_NOCHAIN", 2);

function user_certs()
{
    $uid = (int) $_SESSION['userinfo']['uid'];
    $result = db_query("SELECT id, valid_from, valid_until, subject, cn FROM vhosts.certs WHERE uid=? ORDER BY cn", [$uid]);
    $ret = [];
    while ($i = $result->fetch()) {
        $ret[] = $i;
    }
    #DEBUG($ret);
    return $ret;
}

function user_csr()
{
    $uid = (int) $_SESSION['userinfo']['uid'];
    $result = db_query("SELECT id, created, hostname, bits FROM vhosts.csr WHERE uid=? ORDER BY hostname", [$uid]);
    $ret = [];
    while ($i = $result->fetch()) {
        $ret[] = $i;
    }
    #DEBUG($ret);
    return $ret;
}

function user_has_manual_certs()
{
    foreach (user_certs() as $c) {
        if (!cert_is_letsencrypt($c['id'])) {
            return true;
        }
    }
    foreach (user_csr() as $c) {
        return true;
    }
}


function cert_details($id)
{
    $id = (int) $id;
    $uid = (int) $_SESSION['userinfo']['uid'];

    $result = db_query("SELECT id, lastchange, valid_from, valid_until, subject, cn, chain, cert, `key` FROM vhosts.certs WHERE uid=:uid AND id=:id", [":uid" => $uid, ":id" => $id]);
    if ($result->rowCount() != 1) {
        system_failure("Ungültiges Zertifikat #{$id}");
    }
    return $result->fetch();
}

function cert_is_letsencrypt($id)
{
    $details = cert_details($id);
    #DEBUG($details);
    if (strpos($details['subject'], "Let's Encrypt autogenerated") > 0) {
        return true;
    }
    return false;
}


function csr_details($id)
{
    $id = (int) $id;
    $uid = (int) $_SESSION['userinfo']['uid'];

    $result = db_query("SELECT id, created, hostname, bits, `replace`, csr, `key` FROM vhosts.csr WHERE uid=:uid AND id=:id", [":uid" => $uid, ":id" => $id]);
    if ($result->rowCount() != 1) {
        system_failure("Ungültiger CSR");
    }
    return $result->fetch();
}


function get_available_CAs()
{
    $path = '/etc/apache2/certs/cabundle/';
    $ret = glob($path . '*.pem');
    if (!$ret) {
        system_failure("Konnte die CA-Zertifikate nicht laden");
    }
    DEBUG($ret);
    return $ret;
}


function get_chain($cert)
{
    $certdata = openssl_x509_parse($cert, true);
    if ($certdata === false) {
        system_failure("Das Zertifikat konnte nicht gelesen werden");
    }
    if (!isset($certdata['issuer']['CN'])) {
        return null;
    }
    $result = db_query("SELECT id FROM vhosts.certchain WHERE cn=?", [$certdata['issuer']['CN']]);
    if ($result->rowCount() > 0) {
        $c = $result->fetch();
        //$chainfile = '/etc/apache2/certs/chains/'.$c['id'].'.pem';
        DEBUG("identified fitting certificate chain #" . $c['id']);
        return $c['id'];
    }
}


function validate_certificate($cert, $key)
{
    // Lade private key
    $seckey = openssl_get_privatekey($key);
    if ($seckey === false) {
        system_failure("Der private Schlüssel konnte (ohne Passwort) nicht gelesen werden.");
    }
    // Lade public key
    $pubkey = openssl_get_publickey($cert);
    if ($pubkey === false) {
        system_failure("In dem eingetragenen Zertifikat wurde kein öffentlicher Schlüssel gefunden.");
    }
    // Parse Details über den pubkey
    $pubkeyinfo = openssl_pkey_get_details($pubkey);
    DEBUG($pubkeyinfo);
    if ($pubkeyinfo === false) {
        system_failure("Der öffentliche Schlüssel des Zertifikats konnte nicht gelesen werden");
    }

    // Apache unterstützt nur Schlüssel vom Typ RSA oder DSA
    if ($pubkeyinfo['type'] !== OPENSSL_KEYTYPE_RSA) {
        system_failure("Dieser Schlüssel nutzt einen nicht unterstützten Algorithmus.");
    }

    // Bei ECC-Keys treten kürzere Schlüssellängen auf, die können wir aktuell aber sowieso nicht unterstützen
    // Wir blockieren zu kurze und zu lange Schlüssel hart, da Apache sonst nicht startet
    if ($pubkeyinfo['bits'] < 2048) {
        system_failure("Schlüssellänge ist zu kurz");
    }
    if ($pubkeyinfo['bits'] > 4096) {
        system_failure("Schlüssellänge ist zu lang");
    }

    $x509info = openssl_x509_parse($cert);
    if ($x509info === false) {
        system_failure("Zertifikat konnte nicht verarbeitet werden");
    }
    if (!in_array($x509info['signatureTypeSN'], ["RSA-SHA256", "RSA-SHA385", "RSA-SHA512"])) {
        system_failure("Nicht unterstützer Signatur-Hashalgorithmus!");
    }

    // Prüfe ob Key und Zertifikat zusammen passen
    if (openssl_x509_check_private_key($cert, $key) !== true) {
        DEBUG("Zertifikat und Key passen nicht zusammen: " . openssl_x509_check_private_key($cert, $key));
        return CERT_INVALID;
    }

    // Check von openssl_x509_check_private_key() ist leider nicht ausreichend
    $testdata = base64_encode(random_bytes(32));
    if (openssl_sign($testdata, $signature, $seckey) !== true) {
        system_failure("Kann keine Testsignatur erstellen, Key ungültig!");
    }
    if (openssl_verify($testdata, $signature, $pubkey) !== 1) {
        system_failure("Testsignatur ungültig, Key vermutlich fehlerhaft!");
    }

    $cacerts = ['/etc/ssl/certs'];
    $chain = (int) get_chain($cert);
    if ($chain) {
        $result = db_query("SELECT content FROM vhosts.certchain WHERE id=?", [$chain]);
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
    if ($valid !== true) {
        DEBUG('certificate was not validated as a server certificate with the available chain');
        return CERT_NOCHAIN;
    }

    return CERT_OK;
}


function parse_cert_details($cert)
{
    $certdata = openssl_x509_parse($cert, true);
    DEBUG($certdata);

    $issuer = $certdata['issuer']['CN'];
    if (isset($certdata['issuer']['O'])) {
        $issuer = $certdata['issuer']['O'];
    }
    if (isset($certdata['extensions']['subjectAltName'])) {
        DEBUG("SAN: " . $certdata['extensions']['subjectAltName']);
        $san = [];
        $raw_san = explode(', ', $certdata['extensions']['subjectAltName']);
        foreach ($raw_san as $name) {
            if (!substr($name, 0, 4) == 'DNS:') {
                warning('Unparsable SAN: ' . $name);
                continue;
            }
            $san[] = str_replace('DNS:', '', $name);
        }
        $san = implode("\n", $san);
    } else {
        $san = "\n";
    }
    DEBUG("SAN: <pre>" . $san . "</pre>");
    return ['subject' => $certdata['subject']['CN'] . ' / ' . $issuer, 'cn' => $certdata['subject']['CN'], 'valid_from' => date('Y-m-d', $certdata['validFrom_time_t']), 'valid_until' => date('Y-m-d', $certdata['validTo_time_t']), 'issuer' => $certdata['issuer']['CN'], 'san' => $san];
}


function save_cert($info, $cert, $key)
{
    openssl_pkey_export($key, $key);
    openssl_x509_export($cert, $cert);
    $uid = (int) $_SESSION['userinfo']['uid'];

    db_query(
        "INSERT INTO vhosts.certs (uid, subject, cn, san, valid_from, valid_until, chain, cert, `key`) VALUES (:uid, :subject, :cn, :san, :valid_from, :valid_until, :chain, :cert, :key)",
        [":uid" => $uid, ":subject" => filter_input_oneline($info['subject']), ":cn" => filter_input_oneline($info['cn']), ":san" => $info['san'], ":valid_from" => $info['valid_from'],
            ":valid_until" => $info['valid_until'], ":chain" => get_chain($cert), ":cert" => $cert, ":key" => $key, ]
    );
}


function refresh_cert($id, $info, $cert, $key = null)
{
    openssl_x509_export($cert, $cert);
    $chain = get_chain($cert);

    $id = (int) $id;
    $oldcert = cert_details($id);
    $args = [":subject" => filter_input_oneline($info['subject']),
        ":cn" => filter_input_oneline($info['cn']),
        ":san" => $info['san'],
        ":cert" => $cert,
        ":valid_from" => $info['valid_from'],
        ":valid_until" => $info['valid_until'],
        ":chain" => get_chain($cert),
        ":id" => $id, ];

    $keyop = '';
    if ($key) {
        openssl_pkey_export($key, $key);
        $keyop = ", `key`=:key";
        $args[":key"] = $key;
    }
    db_query("UPDATE vhosts.certs SET subject=:subject, cn=:cn, san=:san, cert=:cert{$keyop}, valid_from=:valid_from, valid_until=:valid_until, chain=:chain WHERE id=:id", $args);
}


function delete_cert($id)
{
    $uid = (int) $_SESSION['userinfo']['uid'];
    $id = (int) $id;

    db_query("DELETE FROM vhosts.certs WHERE uid=? AND id=?", [$uid, $id]);
}

function delete_csr($id)
{
    $uid = (int) $_SESSION['userinfo']['uid'];
    $id = (int) $id;

    db_query("DELETE FROM vhosts.csr WHERE uid=? AND id=?", [$uid, $id]);
}


function split_cn($cn)
{
    $domains = [];
    if (strstr($cn, ',') or strstr($cn, "\n")) {
        $domains = preg_split("/[, \n]+/", $cn);
        DEBUG("Domains:");
        DEBUG($domains);
    } else {
        $domains[] = $cn;
    }
    for ($i = 0;$i != count($domains);$i++) {
        $domains[$i] = filter_input_hostname($domains[$i], true);
    }
    return $domains;
}

function create_csr($cn, $bits)
{
    $domains = split_cn($cn);
    $tmp = [];
    foreach ($domains as $dom) {
        $tmp[] = 'DNS:' . $dom;
    }
    $SAN = "[ v3_req ]\nsubjectAltName = " . implode(', ', $tmp);
    DEBUG($SAN);
    $cn = $domains[0];
    $bits = (int) $bits;
    if ($bits == 0) {
        $bits = 4096;
    }

    $keyfile = tempnam(ini_get('upload_tmp_dir'), 'key');
    $csrfile = tempnam(ini_get('upload_tmp_dir'), 'csr');
    $config = tempnam(ini_get('upload_tmp_dir'), 'config');

    DEBUG("key: " . $keyfile . " / csr: " . $csrfile . " / config: " . $config);

    $c = fopen($config, "w");
    fwrite($c, "[req]
default_bits = {$bits}
default_keyfile = {$keyfile}
encrypt_key = no
distinguished_name      = req_distinguished_name
req_extensions = v3_req

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
{$SAN}
");
    fclose($c);

    $output = '';
    $cmdline = "openssl req -sha256 -new -batch -config {$config} -out {$csrfile}";
    $retval = 0;
    exec($cmdline, $output, $retval);
    DEBUG($output);
    DEBUG($retval);
    if ($retval != 0) {
        system_failure("Die Erzeugung des CSR ist fehlgeschlagen. Ausgabe des OpenSSL-Befehls: " . print_r($output, true));
    }

    $csr = file_get_contents($csrfile);
    $key = file_get_contents($keyfile);

    unlink($csrfile);
    unlink($keyfile);
    unlink($config);

    return [$csr, $key];
}



function save_csr($cn, $bits, $replace = null)
{
    if (!$cn) {
        system_failure("Sie müssen einen Domainname eingeben!");
    }
    $domains = split_cn($cn);
    $cn = $domains[0];
    $san = implode("\n", $domains);
    $csr = null;
    $key = null;
    [$csr, $key] = create_csr(implode(',', $domains), $bits);

    $uid = (int) $_SESSION['userinfo']['uid'];
    db_query(
        "INSERT INTO vhosts.csr (uid, hostname, san, bits, `replace`, csr, `key`) VALUES (:uid, :cn, :san, :bits, :replace, :csr, :key)",
        [":uid" => $uid, ":cn" => $cn, ":san" => $san, ":bits" => $bits,
            ":replace" => $replace, ":csr" => $csr, ":key" => $key, ]
    );
    $id = db_insert_id();
    return $id;
}

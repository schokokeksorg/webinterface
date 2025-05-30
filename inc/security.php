<?php

/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/error.php');
require_once('inc/base.php');
require_once('vendor/autoload.php');

function gen_pw_hash($password)
{
    /* For yescrypt, a 128 bit salt in non-standard base64 is
       needed. We just need random data with valid encoding. */
    $salt = base64_encode(random_bytes(16));
    $salt = rtrim($salt, "=");
    $salt = strtr($salt, "AQgw+/01", "./01AQgw");
    $pwhash = crypt($password, '$y$j9T$' . $salt);
    if (strlen($pwhash) < 13) {
        /* returns a string shorter than 13 chars on failure */
        system_failure("Failed to calculate password hash!");
    }
    return $pwhash;
}


function legacy_pw_verify($password, $hash)
{
    /* Supports legacy SHA1/SHA256 hashes without salt,
       for new use cases use password_verify() instead */
    if ($hash[0] == '$') {
        return password_verify($password, $hash);
    } elseif (strlen($hash) == 40) {
        return hash_equals(sha1($password), $hash);
    } elseif (strlen($hash) == 64) {
        return hash_equals(hash("sha256", $password), $hash);
    }
    return false;
}


function strong_password($password, $user = [])
{
    $pwcheck = config('pwcheck');
    $result = null;
    if ($pwcheck) {
        DEBUG($pwcheck);
        $req = curl_init($pwcheck);
        curl_setopt($req, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($req, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($req, CURLOPT_SSL_VERIFYSTATUS, 1);
        curl_setopt($req, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($req, CURLOPT_TIMEOUT, 5);
        curl_setopt($req, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($req, CURLOPT_POST, 1);
        curl_setopt($req, CURLOPT_POSTFIELDS, "password=" . urlencode($password));
        $result = chop(curl_exec($req));
        DEBUG($result);
    }
    if ($result === 'good') {
        return true;
    } elseif ($result === 'bad') {
        return "Unsere Überprüfung hat ergeben, dass dieses Passwort in bisher veröffentlichten Passwortlisten enthalten ist. Es wird daher nicht akzeptiert.";
    }
    // Kein Online-Check eingerichtet oder der request war nicht erfolgreich
    DEBUG('using Zxcvbn for password check!');
    $passwordchecker = new ZxcvbnPhp\Zxcvbn();
    if ($user) {
        $strength = $passwordchecker->passwordStrength($password, $user);
    } else {
        $strength = $passwordchecker->passwordStrength($password);
    }
    DEBUG('password strength: ' . $strength['score']);
    if ($strength['score'] < 2) {
        return "Das Passwort ist zu einfach!";
    }
    return true;
}


function filter_input_general($input)
{
    if ($input === null) {
        return null;
    }
    $input = (string) $input;
    $filtered = preg_replace('/[\x00-\x09\x0b-\x0c\x0e-\x1f]/', '', $input);
    if ($filtered !== $input) {
        system_failure("Ihre Daten enthielten ungültige Zeichen!");
        logger(LOG_WARNING, 'inc/security', 'filter_input_general', 'Ungültige Daten!');
    }
    return $filtered;
}

function filter_input_oneline($input)
{
    if ($input === null) {
        return null;
    }
    $input = (string) $input;
    $filtered = preg_replace('/[\x00-\x1f]/', '', $input);
    if ($filtered !== $input) {
        system_failure("Ihre Daten enthielten ungültige Zeichen!");
        logger(LOG_WARNING, 'inc/security', 'filter_input_oneline', 'Ungültige Daten!');
    }
    return $filtered;
}


function filter_output_html($data)
{
    if (!$data) {
        return "";
    }
    return htmlspecialchars($data, ENT_QUOTES);
}


function verify_input_ascii($data)
{
    $data = (string) $data;
    $filtered = filter_var($data, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    if ($filtered != $data) {
        logger(LOG_WARNING, 'inc/security', 'verify_input_ascii', 'Ungültige Daten: ' . $data);
        system_failure("Ihre Eingabe enthielt ungültige Zeichen");
    }
    return $filtered;
}


function verify_input_identifier($data)
{
    $data = (string) $data;
    if ($data === "") {
        system_failure("Leerer Bezeichner");
    }
    $filtered = preg_replace("/[^[:alnum:]\_\.\-]/", "", $data);
    if ($filtered !== $data) {
        logger(LOG_WARNING, 'inc/security', 'verify_input_identifier', 'Ungültige Daten: ' . $data);
        system_failure("Ihre Daten enthielten ungültige Zeichen!");
    }
    return $filtered;
}


function filter_input_username($input)
{
    $username = preg_replace("/[^[:alnum:]\_\.\+\-]/", "", $input);
    if ($username === "") {
        system_failure("Leerer Benutzername!");
    }
    return $username;
}

function verify_input_username($input)
{
    if (filter_input_username($input) != $input) {
        logger(LOG_WARNING, 'inc/security', 'verify_input_username', 'Ungültige Daten: ' . $input);
        system_failure("Ihre Daten enthielten ungültige Zeichen!");
    }
}



function filter_input_hostname($input, $wildcard = false)
{
    DEBUG('filter_input_hostname("' . $input . '", $wildcard=' . $wildcard . ')');
    $input = strtolower($input);
    $input = trim($input, "\t\n\r\x00 .");
    if (preg_replace("/[^.]_/", "", $input) != $input) {
        system_failure("Der Unterstrich ist nur als erstes Zeichen eines Hostnames erlaubt.");
    }
    if (preg_replace("/[^[:alnum:]_*\.\-]/u", "", $input) != $input) {
        system_failure("Ihre Daten enthielten ungültige Zeichen!");
    }
    if (preg_match("/^.+\*/", $input)) {
        system_failure("Ihre Daten enthielten ungültige Zeichen (Wildcard-Stern muss ganz vorne stehen)!");
    }
    if (!$wildcard && preg_replace("/^\*/", "", $input) != $input) {
        system_failure("Ihre Daten enthielten ungültige Zeichen (Keine Wildcards erlaubt)!");
    }
    if (strstr($input, '..')) {
        system_failure("Ungültiger Hostname");
    }
    return $input;
}

function verify_input_hostname($input, $wildcard = false)
{
    if (filter_input_hostname($input, $wildcard) != $input) {
        logger(LOG_WARNING, 'inc/security', 'verify_input_hostname', 'Ungültige Daten: ' . $input);
        system_failure("Ihre Daten enthielten ungültige Zeichen!");
    }
}


function verify_input_hostname_utf8($input)
{
    $puny = idn_to_ascii($input, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
    if ($puny === false) {
        system_failure("Ungültiger Hostname! idn " . $input);
    }
    $filter = filter_var($puny, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
    if ($filter !== $puny) {
        system_failure("Ungültiger Hostname! filter " . $input);
    }
}


function verify_input_ipv4($input)
{
    if (!preg_match("/^([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}$/", $input)) {
        system_failure('Keine IP-Adresse');
    }
}


function verify_input_ipv6($input)
{
    // ripped from Perl module Net-IPv6Addr v0.2
    if (!preg_match("/^(([0-9a-f]{1,4}:){7}[0-9a-f]{1,4}|[0-9a-f]{0,4}::|:(?::[a-f0-9]{1,4}){1,6}|(?:[a-f0-9]{1,4}:){1,6}:|(?:[a-f0-9]{1,4}:)(?::[a-f0-9]{1,4}){1,6}|(?:[a-f0-9]{1,4}:){2}(?::[a-f0-9]{1,4}){1,5}|(?:[a-f0-9]{1,4}:){3}(?::[a-f0-9]{1,4}){1,4}|(?:[a-f0-9]{1,4}:){4}(?::[a-f0-9]{1,4}){1,3}|(?:[a-f0-9]{1,4}:){5}(?::[a-f0-9]{1,4}){1,2}|(?:[a-f0-9]{1,4}:){6}(?::[a-f0-9]{1,4}))$/i", $input)) {
        system_failure("Ungültige IPv6-Adresse");
    }
}

function verify_input_recorddata($input)
{
    if (is_string($input) && (strstr($input, "\\") || strstr($input, '"'))) {
        system_failure("Ungültige Zeichen");
    }
}

function filter_quotes($input)
{
    return preg_replace('/["\'`]/', '', $input);
}



function filter_shell($input)
{
    return preg_replace('/[^-[:alnum:]\_\.\+ßäöüÄÖÜ/%§=]/', '', $input);
}

function verify_shell($input)
{
    if (filter_shell($input) != $input) {
        system_failure("Ihre Daten enthielten ungültige Zeichen!");
    }
}


function filter_ssh_key($key, &$fphash = "")
{
    $filtered = trim(str_replace(["\r", "\n"], ' ', $key));
    $keyparts = explode(" ", $filtered);

    if ((count($keyparts) > 3) || (count($keyparts) < 2)) {
        system_failure("Ungültiger SSH-Key!");
    }

    if (preg_match("/^[a-z0-9]+-[a-z0-9-]+$/", $keyparts[0]) === 0) {
        system_failure("Ungültiger SSH-Key!");
    }

    if (base64_decode($keyparts[1], 1) == false) {
        system_failure("Ungültiger SSH-Key!");
    }

    if ((count($keyparts) === 3) && (preg_match("/^[a-zA-Z0-9@._-]+$/", $keyparts[2]) === 0)) {
        system_failure("Ungültige Zeichen im Kommentar des SSH-Keys!");
    }

    if ($keyparts[0] == "ssh-dss") {
        system_failure("DSA-Keys werden nicht unterstützt!");
    }

    if (count($keyparts) === 2) {
        $fkey = $keyparts[0] . " " . $keyparts[1];
    } else {
        $fkey = $keyparts[0] . " " . $keyparts[1] . " " . $keyparts[2];
    }

    $descr = [
        0 => ["pipe", "r"],
        1 => ["pipe", "w"],
        2 => ["pipe", "w"],
    ];
    $sshcmd = proc_open("ssh-keygen -l -f -", $descr, $pipes, null, null);
    fwrite($pipes[0], $fkey);
    fclose($pipes[0]);
    $fphash = fread($pipes[1], 1024);
    if (proc_close($sshcmd) !== 0) {
        system_failure("Ungültiger SSH-Key laut ssh-keygen!");
    }

    $fphash = explode(" ", $fphash)[1];
    if ((strlen($fphash) != 50) || (substr($fphash, 0, 7) != "SHA256:")) {
        system_failure("Interner Fehler: Fingerprint im falschen Format");
    }
    $fphash = substr($fphash, 7);

    return $fkey;
}


function check_path($input)
{
    DEBUG("checking {$input} for valid path name");
    if ($input != filter_output_html($input)) {
        logger(LOG_WARNING, 'inc/security', 'check_path', 'HTML-Krams im Pfad: ' . $input);
        DEBUG("HTML-Krams im Pfad");
        return false;
    }
    $components = explode("/", $input);
    foreach ($components as $item) {
        if ($item == '..') {
            logger(LOG_WARNING, 'inc/security', 'check_path', '»..« im Pfad: ' . $input);
            DEBUG("»..« im Pfad");
            return false;
        }
        if (strlen($item) > 255) {
            return false;
        }
    }
    if (strlen($input) > 2048) {
        return false;
    }
    return (preg_match('/^[ A-Za-z0-9.@\/_-]*$/', $input) == 1);
}


function in_homedir($path)
{
    DEBUG("Prüfe »{$path}«");
    if (!check_path($path)) {
        DEBUG('Kein Pfad');
        return false;
    }
    if (!isset($_SESSION['userinfo']['homedir'])) {
        DEBUG("Kann homedir nicht ermitteln");
        return false;
    }
    return strncmp($_SESSION['userinfo']['homedir'], $path, strlen($_SESSION['userinfo']['homedir'])) == 0;
}

function check_date($input)
{
    return (bool) preg_match("/[0-9]{4}-(0?[1-9]|10|11|12)-([012]?[0-9]|30|31)/", $input);
}


function check_emailaddr($input)
{
    return (bool) filter_var($input, FILTER_VALIDATE_EMAIL) == $input;
}

function check_domain($input)
{
    return (bool) preg_match("/^[a-z0-9\.\-]+\.[a-z\-]{2,63}$/i", $input);
}

function check_input_types($input, $types)
{
    foreach ($types as $key => $type) {
        if (!array_key_exists($key, $input)) {
            system_failure("Interner Fehler bei Eingabevariablen");
        }
        if ($type === 'int') {
            if ($input[$key] !== (string) (int) $input[$key]) {
                system_failure("Interner Fehler bei Eingabevariablen");
            }
            continue;
        } elseif ($type === 'string') {
            if (!is_string($input[$key])) {
                system_failure("Interner Fehler bei Eingabevariablen");
            }
        } else {
            system_failure("Interner Fehler: Ungültier Typ");
        }
    }
}

<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2018 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/error.php');
require_once('vendor/autoload.php');


function strong_password($password, $user = array())
{
    $passwordchecker = new ZxcvbnPhp\Zxcvbn();
    $strength = $passwordchecker->passwordStrength($password, $user);

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
    return htmlspecialchars(iconv('UTF-8', 'UTF-8', $input), ENT_QUOTES, 'UTF-8');
}


function verify_input_general($input)
{
    if (filter_input_general($input) !== $input) {
        system_failure("Ihre Daten enthielten ungültige Zeichen!");
        logger(LOG_WARNING, 'inc/security', 'verify_input_general', 'Ungültige Daten: '.$input);
    } else {
        return $input;
    }
}


function filter_input_username($input)
{
    $username=preg_replace("/[^[:alnum:]\_\.\+\-]/", "", $input);
    if ($username === "") {
        system_failure("Leerer Benutzername!");
    }
    return $username;
}

function verify_input_username($input)
{
    if (filter_input_username($input) != $input) {
        logger(LOG_WARNING, 'inc/security', 'verify_input_username', 'Ungültige Daten: '.$input);
        system_failure("Ihre Daten enthielten ungültige Zeichen!");
    }
}



function filter_input_hostname($input, $wildcard=false)
{
    // FIXME: Eine "filter"-Funktion sollte keinen system_failure verursachen sondern einfach einen bereinigten String liefern.

    DEBUG('filter_input_hostname("'.$input.'", $wildcard='.$wildcard.')');
    $input = strtolower($input);
    $input = rtrim($input, "\t\n\r\x00 .");
    $input = ltrim($input, "\t\n\r\x00 .");
    if (preg_replace("/[^.]_/", "", $input) != $input) {
        system_failure("Der Unterstrich ist nur als erstes Zeichen eines Hostnames erlaubt.");
    }
    if (preg_replace("/[^[:alnum:]_*\.\-]/u", "", $input) != $input) {
        system_failure("Ihre Daten enthielten ungültige Zeichen!");
    }
    if (preg_match("/^.+\*/", $input)) {
        system_failure("Ihre Daten enthielten ungültige Zeichen (Wildcard-Stern muss ganz vorne stehen)!");
    }
    if (! $wildcard && preg_replace("/^\*/", "", $input) != $input) {
        system_failure("Ihre Daten enthielten ungültige Zeichen (Keine Wildcards erlaubt)!");
    }
    if (strstr($input, '..')) {
        system_failure("Ungültiger Hostname");
    }
    return $input;
}

function verify_input_hostname($input, $wildcard=false)
{
    if (filter_input_hostname($input, $wildcard) != $input) {
        logger(LOG_WARNING, 'inc/security', 'verify_input_hostname', 'Ungültige Daten: '.$input);
        system_failure("Ihre Daten enthielten ungültige Zeichen!");
    }
}


function verify_input_ipv4($input)
{
    if (! preg_match("/^([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}$/", $input)) {
        system_failure('Keine IP-Adresse');
    }
}


function verify_input_ipv6($input)
{
    // ripped from Perl module Net-IPv6Addr v0.2
    if (! preg_match("/^(([0-9a-f]{1,4}:){7}[0-9a-f]{1,4}|[0-9a-f]{0,4}::|:(?::[a-f0-9]{1,4}){1,6}|(?:[a-f0-9]{1,4}:){1,6}:|(?:[a-f0-9]{1,4}:)(?::[a-f0-9]{1,4}){1,6}|(?:[a-f0-9]{1,4}:){2}(?::[a-f0-9]{1,4}){1,5}|(?:[a-f0-9]{1,4}:){3}(?::[a-f0-9]{1,4}){1,4}|(?:[a-f0-9]{1,4}:){4}(?::[a-f0-9]{1,4}){1,3}|(?:[a-f0-9]{1,4}:){5}(?::[a-f0-9]{1,4}){1,2}|(?:[a-f0-9]{1,4}:){6}(?::[a-f0-9]{1,4}))$/i", $input)) {
        system_failure("Ungültige IPv6-Adresse");
    }
}

function verify_input_recorddata($input)
{
    if (strstr($input, "\\") || strstr($input, '"')) {
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


function filter_ssh_key($key)
{
    $keyparts = explode(" ", trim($key));

    if ((count($keyparts) > 3) || (count($keyparts) < 2)) {
        system_failure("Ungültiger SSH-Key!");
    }

    if (preg_match("/^[a-z0-9]+-[a-z0-9-]+$/", $keyparts[0]) === 0) {
        system_failure("Ungültiger SSH-Key!");
    }

    if (base64_decode($keyparts[1], 1) == false) {
        system_failure("Ungültiger SSH-Key!");
    }

    if ((count($keyparts) === 3) && (preg_match("/^[a-zA-Z0-9@.-_]+$/", $keyparts[2]) === 0)) {
        system_failure("Ungültige Zeichen im Kommentar des SSH-Keys!");
    }

    if (count($keyparts) === 2) {
        return $keyparts[0]." ".$keyparts[1];
    } else {
        return $keyparts[0]." ".$keyparts[1]." ".$keyparts[2];
    }
}


function check_path($input)
{
    DEBUG("checking {$input} for valid path name");
    if ($input != filter_input_general($input)) {
        logger(LOG_WARNING, 'inc/security', 'check_path', 'HTML-Krams im Pfad: '.$input);
        DEBUG("HTML-Krams im Pfad");
        return false;
    }
    $components = explode("/", $input);
    foreach ($components as $item) {
        if ($item == '..') {
            logger(LOG_WARNING, 'inc/security', 'check_path', '»..« im Pfad: '.$input);
            DEBUG("»..« im Pfad");
            return false;
        }
    }
    return (preg_match('/^[ A-Za-z0-9.@\/_-]*$/', $input) == 1);
}


function in_homedir($path)
{
    DEBUG("Prüfe »{$path}«");
    if (! check_path($path)) {
        DEBUG('Kein Pfad');
        return false;
    }
    if (! isset($_SESSION['userinfo']['homedir'])) {
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

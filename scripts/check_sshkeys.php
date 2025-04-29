#!/usr/bin/php
<?php

/*

  SPDX-License-Identifier: 0BSD
  schokokeks.org Hosting, https://schokokeks.org/

  Checks dyndns keys in database with filter_ssh_key()
*/

if (PHP_SAPI !== 'cli') {
    die("cli only");
}

require_once("config.php");
require_once("inc/security.php");
require_once("inc/theme.php");
require_once("session/checkuser.php");

$res = db_query("SELECT sshkey FROM dns.dyndns WHERE sshkey IS NOT NULL");

while ($ob = $res->fetch()) {
    $key = $ob["sshkey"];
    $fkey = filter_ssh_key($key, $hash);
    if ($key !== $fkey) {
        echo "Does not match:\n";
        echo $key . "\n";
        echo $fkey . "\n";
        echo "Fingerprint: $hash";
    }
}

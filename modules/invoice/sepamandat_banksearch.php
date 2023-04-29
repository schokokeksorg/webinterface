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
require_once('inc/debug.php');

require_once('invoice.php');

$result = [
    "iban_ok" => 0,
    "iban" => null,
    "bic" => null,
    "bankname" => null,
];

$iban = null;
if (isset($_GET['iban'])) {
    $iban = $_GET['iban'];
} elseif (isset($_GET['kto']) && isset($_GET['blz'])) {
    $iban = find_iban($_GET['blz'], $_GET['kto']);
}
if ($iban != null) {
    $iban_ok = (verify_iban($iban) ? '1' : '0');
    if ($iban_ok) {
        $result["iban_ok"] = 1;
        $result["iban"] = $iban;
        $bank = get_bank_info($iban);
        $result["bic"] = $bank["bic"];
        $result["bankname"] = $bank["name"];
    }
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode($result);
die();

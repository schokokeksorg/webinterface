<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('passkey.php');
require_once('inc/base.php');

require_once('vendor/autoload.php');

$req = filter_input(INPUT_POST, 'req');

// Relying Party == Hostname
$rpId = $_SERVER['HTTP_HOST'];

$WebAuthn = new lbuchs\WebAuthn\WebAuthn(config('company_name') . ' Webinterface', $rpId, ["none"]);

if ($req == 'getCreateArgs') {
    require_role(ROLE_SYSTEMUSER);
    $userId = dechex($_SESSION['userinfo']['uid']); // Hex-formatted internal ID not displayed to the user
    if (strlen($userId) % 2 == 1) {
        $userId = "0" . $userId;
    }
    $userName = $_SESSION['userinfo']['username'];
    $_SESSION['passkey_handle'] = filter_input(INPUT_POST, "handle");
    $userDisplayName = $_SESSION['userinfo']['name'];
    if ($_SESSION['passkey_handle']) {
        $userDisplayName = $userDisplayName . " ({$_SESSION['passkey_handle']})";
    }

    $requireResidentKey = 'required';
    $userVerification = 'preferred';

    $timeout = 3 * 60;

    $createArgs = $WebAuthn->getCreateArgs(\hex2bin($userId), $userName, $userDisplayName, $timeout, $requireResidentKey, $userVerification);

    // save challange to session. you have to deliver it to processGet later.
    $_SESSION['challenge'] = ($WebAuthn->getChallenge())->getBinaryString();

    header('Content-Type: application/json');
    print(json_encode($createArgs));
} elseif ($req == 'processCreate') {
    require_role(ROLE_SYSTEMUSER);
    $client = $_POST['client'];
    $attest = $_POST['attest'];

    try {
        $data = $WebAuthn->processCreate(
            base64_decode($_POST["client"]),
            base64_decode($_POST["attest"]),
            $_SESSION["challenge"],
            true,
            true,
            false,
            false
        );
    } catch (Exception $ex) {
        logger(LOG_ERR, "modules/loginsecurity/passkey_ajax", "loginsecurity", "processCreate failed with {$ex}");
        print("Error");
        die();
    }

    save_passkey($data, $_SESSION['passkey_handle']);
    unset($_SESSION['passkey_handle']);
    print("OK");
    success_msg("Der Passkey wurde gespeichert!");
    die();
} elseif ($req == 'getGetArgs') {
    $args = $WebAuthn->getGetArgs([], 30);
    $_SESSION["challenge"] = ($WebAuthn->getChallenge())->getBinaryString();
    header('Content-Type: application/json');
    print(json_encode($args));
    die();
} elseif ($req == 'processGet') {
    $id = base64_decode($_POST["id"]);
    $savedData = get_passkey($id);
    if (!$savedData) {
        print("Invalid credentials");
        die();
    }
    try {
        $WebAuthn->processGet(
            base64_decode($_POST["client"]),
            base64_decode($_POST["auth"]),
            base64_decode($_POST["sig"]),
            $savedData['credentialPublicKey'],
            $_SESSION["challenge"]
        );
        // DO WHATEVER IS REQUIRED AFTER VALIDATION
        echo "OK";
        $login = ($_POST['login'] == "true");
        if ($login) {
            $uid = $savedData['uid'];
            require_once("session/start.php");
            $role = find_role($uid, '', true);
            setup_session($role, $uid, 'passkey');
            unset($_SESSION['challenge']);
            die();
        } else {
            success_msg("Die Identifikation mit dem Passkey »{$savedData['handle']}« hat funktioniert!");
        }
    } catch (Exception $ex) {
        logger(LOG_ERR, "modules/loginsecurity/passkey_ajax", "loginsecurity", "processGet failed with {$ex}");
        print('Error');
        die();
    }
    die();
}

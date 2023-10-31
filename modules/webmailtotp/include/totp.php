<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('vendor/autoload.php');

function account_has_totp($username)
{
    $result = db_query("SELECT id FROM mail.webmail_totp WHERE email=?", [$username]);
    if ($result->rowCount() > 0) {
        $tmp = $result->fetch();
        $id = $tmp['id'];
        return $id;
    } else {
        return false;
    }
}



function validate_password($username, $password)
{
    $args[":username"] = $username;
    $result = db_query("SELECT account, cryptpass FROM mail.courier_mailaccounts WHERE account=:username UNION SELECT account, cryptpass FROM mail.courier_virtual_accounts WHERE account=:username", $args);
    if ($result->rowCount() != 1) {
        // Kein Account mit dem Namen oder Name nicht eindeutig
        return false;
    }
    $account = $result->fetch();
    return (crypt($password, $account['cryptpass']) == $account['cryptpass']);
}


function store_webmail_password($username, $oldpw, $newpw)
{
    $qual = strong_password($newpw);
    if ($qual !== true) {
        system_failure('Fehler beim Webmail-Passwort: ' . $qual);
    }
    $secret = $newpw;
    if (strlen($oldpw) > strlen($newpw)) {
        $secret = str_pad($newpw, strlen($oldpw), $newpw);
    }
    if (strlen($oldpw) < strlen($newpw)) {
        $newpw = substr($newpw, 0, strlen($oldpw));
    }
    if (strlen($oldpw) != strlen($secret)) {
        system_failure('Interner Fehler: Passwörter sind nicht gleich lang');
    }
    $code = '';
    for ($i = 0 ; $i != strlen($oldpw) ; $i++) {
        $code .= chr(ord($oldpw[$i]) ^ ord($secret[$i]));
    }
    DEBUG([$oldpw, $newpw]);
    $args = [":uid" => $_SESSION['userinfo']['uid'],
                ":username" => $username,
                ":code" => base64_encode($code), ];

    db_query("REPLACE INTO mail.webmail_totp (useraccount, email, webmailpass) VALUES (:uid, :username, :code)", $args);
}


function decode_webmail_password($crypted, $webmailpw)
{
    $crypted = base64_decode($crypted);
    $secret = $webmailpw;
    if (strlen($crypted) > strlen($webmailpw)) {
        $secret = str_pad($webmailpw, strlen($crypted), $webmailpw);
    }
    if (strlen($crypted) < strlen($webmailpw)) {
        $webmailpw = substr($webmailpw, 0, strlen($crypted));
    }
    $clear = '';
    for ($i = 0 ; $i != strlen($crypted) ; $i++) {
        $clear .= chr(ord($crypted[$i]) ^ ord($secret[$i]));
    }
    DEBUG('decrypted: ' . $clear);
    return $clear;
}


function get_imap_password($username, $webmailpass)
{
    $result = db_query("SELECT webmailpass FROM mail.webmail_totp WHERE email=?", [$username]);
    $tmp = $result->fetch();

    $crypted = $tmp['webmailpass'];

    $clear = decode_webmail_password($crypted, $webmailpass);
    return $clear;
}


function check_webmail_password($username, $webmailpass)
{
    $clear = get_imap_password($username, $webmailpass);
    return validate_password($username, $clear);
}


function generate_secret($username)
{
    $ga = new PHPGangsta_GoogleAuthenticator();

    $secret = $ga->createSecret();
    DEBUG('GA-Secret: ' . $secret);
    DEBUG('QrCode: ' . $ga->getQRCodeGoogleUrl('Blog', $secret));
    $args = [":secret" => $secret, ":username" => $username];
    db_query("UPDATE mail.webmail_totp SET totp_secret=:secret WHERE email=:username", $args);
    return $secret;
}

function check_locked($username)
{
    $result = db_query("SELECT 1 FROM mail.webmail_totp WHERE unlock_timestamp IS NOT NULL and unlock_timestamp > NOW() AND email=?", [$username]);
    return ($result->rowCount() > 0);
}

function check_totp($username, $code)
{
    if (check_blacklist($username, $code)) {
        DEBUG('Replay-Attack');
        return false;
    }

    $result = db_query("SELECT totp_secret, failures FROM mail.webmail_totp WHERE email=? AND (unlock_timestamp IS NULL OR unlock_timestamp <= NOW())", [$username]);
    $tmp = $result->fetch();
    $secret = $tmp['totp_secret'];

    $ga = new PHPGangsta_GoogleAuthenticator();

    $checkResult = $ga->verifyCode($secret, $code, 2);    // 2 = 2*30sec clock tolerance
    if ($checkResult) {
        db_query("UPDATE mail.webmail_totp SET failures = 0, unlock_timestamp=NULL WHERE email=?", [$username]);
        blacklist_token($username, $code);
        DEBUG('OK');
    } else {
        if ($tmp['failures'] > 0 && $tmp['failures'] % 5 == 0) {
            db_query("UPDATE mail.webmail_totp SET failures = failures+1, unlock_timestamp = NOW() + INTERVAL 5 MINUTE WHERE email=?", [$username]);
        } else {
            db_query("UPDATE mail.webmail_totp SET failures = failures+1 WHERE email=?", [$username]);
        }

        DEBUG('FAILED');
    }
    return $checkResult;
}

function generate_qrcode_image($secret)
{
    $url = 'otpauth://totp/Webmail?secret=' . $secret;

    $descriptorspec = [
    0 => ["pipe", "r"],  // STDIN ist eine Pipe, von der das Child liest
    1 => ["pipe", "w"],  // STDOUT ist eine Pipe, in die das Child schreibt
    2 => ["pipe", "w"],
  ];

    $process = proc_open('qrencode -t PNG -s 5 -o -', $descriptorspec, $pipes);

    if (is_resource($process)) {
        // $pipes sieht nun so aus:
        // 0 => Schreibhandle, das auf das Child STDIN verbunden ist
        // 1 => Lesehandle, das auf das Child STDOUT verbunden ist

        fwrite($pipes[0], $url);
        fclose($pipes[0]);

        $pngdata = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        // Es ist wichtig, dass Sie alle Pipes schließen bevor Sie
        // proc_close aufrufen, um Deadlocks zu vermeiden
        $return_value = proc_close($process);

        return $pngdata;
    } else {
        warning('Es ist ein interner Fehler im Webinterface aufgetreten, aufgrund dessen kein QR-Code erstellt werden kann. Sollte dieser Fehler mehrfach auftreten, kontaktieren Sie bitte die Administratoren.');
    }
}

function accountname($id)
{
    $args = [":id" => $id,
                ":uid" => $_SESSION['userinfo']['uid'], ];
    $result = db_query("SELECT email FROM mail.webmail_totp WHERE id=:id AND useraccount=:uid", $args);
    if ($tmp = $result->fetch()) {
        return $tmp['email'];
    }
}


function delete_totp($id)
{
    $args = [":id" => $id,
                ":uid" => $_SESSION['userinfo']['uid'], ];

    db_query("DELETE FROM mail.webmail_totp WHERE id=:id AND useraccount=:uid", $args);
}


function blacklist_token($email, $token)
{
    $args = [":email" => $email, ":token" => $token];
    db_query("INSERT INTO mail.webmail_totp_blacklist (timestamp, email, token) VALUES (NOW(), :email, :token)", $args);
}

function check_blacklist($email, $token)
{
    $args = [":email" => $email, ":token" => $token];
    db_query("DELETE FROM mail.webmail_totp_blacklist WHERE timestamp < NOW() - INTERVAL 10 MINUTE");
    $result = db_query("SELECT id FROM mail.webmail_totp_blacklist WHERE email=:email AND token=:token", $args);
    return ($result->rowCount() > 0);
}

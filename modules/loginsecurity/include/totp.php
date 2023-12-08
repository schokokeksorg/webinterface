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

function list_systemuser_totp($uid = null)
{
    if (!$uid) {
        $uid = (int)$_SESSION['userinfo']['uid'];
    }
    $result = db_query("SELECT id, description, setuptime FROM system.systemuser_totp WHERE uid=?", [$uid]);
    $ret = [];
    while ($line = $result->fetch()) {
        $ret[] = $line;
    }
    return $ret;
}

function check_systemuser_password($password)
{
    $result = db_query("SELECT passwort AS password FROM system.passwoerter WHERE uid=:uid", [":uid" => $_SESSION['userinfo']['uid']]);
    if (@$result->rowCount() > 0) {
        $entry = $result->fetch(PDO::FETCH_OBJ);
        $db_password = $entry->password;
        $hash = crypt($password, $db_password);
        if ($hash == $db_password) {
            return true;
        }
    }
    return false;
}


function generate_systemuser_secret()
{
    $ga = new PHPGangsta_GoogleAuthenticator();

    $secret = $ga->createSecret();
    DEBUG('GA-Secret: ' . $secret);
    return $secret;
}

function check_systemuser_locked($uid)
{
    if (!$uid) {
        $uid = $_SESSION['userinfo']['uid'];
    }
    $result = db_query("SELECT 1 FROM system.systemuser_totp WHERE unlock_timestamp IS NOT NULL and unlock_timestamp > NOW() AND uid=?", [$uid]);
    return ($result->rowCount() > 0);
}

function check_systemuser_totp($uid, $code)
{
    $ga = new PHPGangsta_GoogleAuthenticator();
    $secret = null;
    $checkResult = false;
    if (isset($_SESSION['totp_secret'])) {
        // Während des Setup
        $secret = $_SESSION['totp_secret'];
        $checkResult = $ga->verifyCode($secret, $code, 2);    // 2 = 2*30sec clock tolerance
    } else {
        // Normalbetrieb
        if (!$uid) {
            $uid = $_SESSION['userinfo']['uid'];
        }
        $result = db_query("SELECT id,secret,failures FROM system.systemuser_totp WHERE uid=? AND (unlock_timestamp IS NULL OR unlock_timestamp<NOW())", [$uid]);
        while ($tmp = $result->fetch()) {
            $totp_id = $tmp['id'];
            $secret = $tmp['secret'];

            if (check_systemuser_blacklist($uid, $totp_id, $code)) {
                DEBUG('Replay-Attack');
                return false;
            }

            $checkResult = $ga->verifyCode($secret, $code, 2);    // 2 = 2*30sec clock tolerance
            if ($checkResult) {
                db_query("UPDATE system.systemuser_totp SET lastused=CURRENT_TIMESTAMP() WHERE id=?", [$totp_id]);
                blacklist_systemuser_token($uid, $totp_id, $code);
                DEBUG('OK');
            } else {
                DEBUG('TOTP-Code war falsch, checke gegen Restoretoken');
                if ($code == totp_restoretoken($totp_id)) {
                    // Das Restoretoken wird als gültiges OTP anerkannt (eigentlich nicht okay aber einfacher)
                    return true;
                }
                if ($tmp['failures'] > 0 && $tmp['failures'] % 5 == 0) {
                    db_query("UPDATE system.systemuser_totp SET failures = failures+1, unlock_timestamp = NOW() + INTERVAL 5 MINUTE WHERE id=?", [$totp_id]);
                } else {
                    db_query("UPDATE system.systemuser_totp SET failures = failures+1 WHERE id=?", [$totp_id]);
                }
                DEBUG('FAILED');
            }
            if ($checkResult) {
                // Wenn einer stimmt, dann reicht uns das
                return true;
            }
        }
    }
    return $checkResult;
}

function save_totp_config($description)
{
    if (!isset($_SESSION['totp_secret'])) {
        system_failure("Session kaputt");
    }
    $args = [":uid" => $_SESSION['userinfo']['uid'], ":secret" => $_SESSION['totp_secret'], ":restoretoken" => random_string(30), ":description" => $description];
    db_query("INSERT INTO system.systemuser_totp (description, uid, secret, restoretoken) VALUES (:description, :uid, :secret, :restoretoken)", $args);
    unset($_SESSION['totp_secret']);
    return db_insert_id();
}

function totp_restoretoken($totp_id)
{
    $result = db_query(
        "SELECT restoretoken FROM system.systemuser_totp WHERE id=:id",
        [":id" => $totp_id]
    );
    $data = $result->fetch();
    DEBUG("Restoretoken für #{$totp_id} ist {$data['restoretoken']}");
    return $data['restoretoken'];
}

function generate_systemuser_qrcode_image($secret)
{
    $username = $_SESSION['userinfo']['username'];
    $url = 'otpauth://totp/' . $username . '@schokokeks.org?secret=' . $secret;

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


function delete_systemuser_totp($id)
{
    $args = [":id" => $id,
                ":uid" => $_SESSION['userinfo']['uid'], ];

    db_query("DELETE FROM system.systemuser_totp WHERE id=:id AND uid=:uid", $args);
}


function blacklist_systemuser_token($uid, $totpid, $token)
{
    $args = [":totpid" => $totpid, ":uid" => $uid, ":token" => $token];
    db_query("INSERT INTO system.systemuser_totp_used (timestamp, uid, totpid, token) VALUES (NOW(), :uid, :totpid, :token)", $args);
}

function check_systemuser_blacklist($uid, $totpid, $token)
{
    $args = [":totpid" => $totpid, ":uid" => $uid, ":token" => $token];
    db_query("DELETE FROM system.systemuser_totp_used WHERE timestamp < NOW() - INTERVAL 10 MINUTE");
    $result = db_query("SELECT id FROM system.systemuser_totp_used WHERE uid=:uid AND totpid=:totpid AND token=:token", $args);
    return ($result->rowCount() > 0);
}

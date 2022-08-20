<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('session/checkuser.php');

function user_customer_match($cust, $user)
{
    $args = [":cid" => $cust,
                ":user" => $user, ];
    $result = db_query("SELECT uid FROM system.useraccounts WHERE kunde=:cid AND username=:user AND kundenaccount=1", $args);
    if ($result->rowCount() > 0) {
        return true;
    }
    return false;
}

function find_username($input)
{
    $args = [":user" => $input];
    $result = db_query("SELECT username FROM system.useraccounts WHERE username=:user AND kundenaccount=1", $args);
    if ($result->rowCount() > 0) {
        $line = $result->fetch();
        return $line['username'];
    } else {
        return false;
    }
}

function customer_has_email($customerno, $email)
{
    $args = [":cid" => $customerno,
                ":email" => $email, ];
    $result = db_query("SELECT NULL FROM kundendaten.kunden WHERE id=:cid AND (email=:email OR email_extern=:email OR email_rechnung=:email)", $args);
    return ($result->rowCount() > 0);
}


function validate_token($customerno, $token)
{
    expire_tokens();
    $args = [":cid" => $customerno,
                ":token" => $token, ];
    $result = db_query("SELECT NULL FROM kundendaten.kunden WHERE id=:cid AND token=:token", $args);
    return ($result->rowCount() > 0);
}


function get_uid_for_token($token)
{
    expire_tokens();
    $result = db_query("SELECT uid FROM system.usertoken WHERE token=?", [$token]);
    if ($result->rowCount() == 0) {
        return null;
    }
    $data = $result->fetch();
    return $data['uid'];
}

function get_username_for_uid($uid)
{
    $result = db_query("SELECT username FROM system.useraccounts WHERE uid=?", [$uid]);
    if ($result->rowCount() != 1) {
        system_failure("Unexpected number of users with this uid (!= 1)!");
    }
    $item = $result->fetch();
    return $item['username'];
}

function validate_uid_token($uid, $token)
{
    expire_tokens();
    $args = [":uid" => $uid,
                ":token" => $token, ];
    $result = db_query("SELECT NULL FROM system.usertoken WHERE uid=:uid AND token=:token", $args);
    return ($result->rowCount() > 0);
}


function expire_tokens()
{
    $expire = "1 DAY";
    db_query("UPDATE kundendaten.kunden SET token=NULL, token_create=NULL WHERE token_create < NOW() - INTERVAL {$expire};");
    db_query("DELETE FROM system.usertoken WHERE expire < NOW();");
}

function invalidate_customer_token($customerno)
{
    db_query("UPDATE kundendaten.kunden SET token=NULL, token_create=NULL WHERE id=?", [$customerno]);
}

function invalidate_systemuser_token($uid)
{
    db_query("DELETE FROM system.usertoken WHERE uid=?", [$uid]);
}

function create_token($username)
{
    expire_tokens();
    $result = db_query("SELECT uid FROM system.useraccounts WHERE username=?", [$username]);
    $uid = (int) $result->fetch()['uid'];

    $result = db_query("SELECT created FROM system.usertoken WHERE uid=?", [$uid]);
    if ($result->rowCount() > 0) {
        system_failure("Für Ihr Benutzerkonto ist bereits eine Passwort-Erinnerung versendet worden. Bitte wenden Sie sich an den Support wenn Sie diese nicht erhalten haben.");
    }

    $args = [":uid" => $uid,
                ":token" => random_string(16), ];
    db_query("INSERT INTO system.usertoken VALUES (:uid, NOW(), NOW() + INTERVAL 1 DAY, :token)", $args);
    return true;
}


function emailaddress_for_user($username)
{
    $result = db_query("SELECT k.email FROM kundendaten.kunden AS k INNER JOIN system.useraccounts AS u ON (u.kunde=k.id) WHERE u.username=?", [$username]);
    $data = $result->fetch();
    return $data['email'];
}


function get_customer_token($customerno)
{
    expire_tokens();
    $result = db_query("SELECT token FROM kundendaten.kunden WHERE id=? AND token IS NOT NULL", [$customerno]);
    if ($result->rowCount() < 1) {
        system_failure("Kann das Token nicht auslesen!");
    }
    return $result->fetch(PDO::FETCH_OBJ)->token;
}


function get_user_token($username)
{
    $result = db_query("SELECT token FROM system.usertoken AS t INNER JOIN system.useraccounts AS u USING (uid) WHERE username=?", [$username]);
    $tmp = $result->fetch();
    return $tmp['token'];
}

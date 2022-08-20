<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/security.php');

function do_ajax_cert_login()
{
    global $prefix;
    require_once('inc/jquery.php');
    javascript('certlogin.js', 'index');
}

function get_logins_by_cert($cert)
{
    $result = db_query("SELECT type,username,startpage FROM system.clientcert WHERE cert=? ORDER BY type,username", [$cert]);
    if ($result->rowCount() < 1) {
        DEBUG("No certlogin found for this cert!");
        return null;
    } else {
        $ret = [];
        while ($row = $result->fetch()) {
            $ret[] = $row;
        }
        DEBUG("Logins for this cert:");
        DEBUG($ret);
        return $ret;
    }
}

function get_cert_by_id($id)
{
    $id = (int) $id;
    if ($id == 0) {
        system_failure('no ID');
    }
    $result = db_query("SELECT id,dn,issuer,serial,valid_from,valid_until,cert,username,startpage FROM system.clientcert WHERE `id`=?", [$id]);
    if ($result->rowCount() < 1) {
        return null;
    }
    $ret = $result->fetch();
    DEBUG($ret);
    return $ret;
}


function get_certs_by_username($username)
{
    if ($username == '') {
        system_failure('empty username');
    }
    $result = db_query("SELECT id,dn,issuer,serial,valid_from,valid_until,cert,startpage FROM system.clientcert WHERE `username`=?", [$username]);
    if ($result->rowCount() < 1) {
        return null;
    }
    while ($row = $result->fetch()) {
        $ret[] = $row;
    }
    return $ret;
}


function add_clientcert($certdata, $dn, $issuer, $serial, $vstart, $vend, $startpage=null)
{
    $type = null;
    $username = null;
    if ($_SESSION['role'] & ROLE_SYSTEMUSER) {
        $type = 'user';
        $username = $_SESSION['userinfo']['username'];
        if (isset($_SESSION['subuser'])) {
            $username = $_SESSION['subuser'];
            $type = 'subuser';
        }
    } elseif ($_SESSION['role'] & ROLE_VMAIL_ACCOUNT) {
        $type = 'email';
        $username = $_SESSION['mailaccount'];
    }
    if (! $type || ! $username) {
        system_failure('cannot get type or username of login');
    }
    if ($startpage &&  ! check_path($startpage)) {
        system_failure('Startseite kaputt');
    }

    if ($certdata == '') {
        system_failure('Kein Zertifikat');
    }

    $args = [":dn" => $dn,
                ":issuer" => $issuer,
                ":serial" => $serial,
                ":vstart" => $vstart,
                ":vend" => $vend,
                ":certdata" => $certdata,
                ":type" => $type,
                ":username" => $username,
                ":startpage" => $startpage, ];
    DEBUG($args);

    db_query("INSERT INTO system.clientcert (`dn`, `issuer`, `serial`, `valid_from`, `valid_until`, `cert`, `type`, `username`, `startpage`) 
VALUES (:dn, :issuer, :serial, :vstart, :vend, :certdata, :type, :username, :startpage)", $args);
}


function delete_clientcert($id)
{
    $id = (int) $id;
    $type = null;
    $username = null;
    if ($_SESSION['role'] & ROLE_SYSTEMUSER) {
        $type = 'user';
        $username = $_SESSION['userinfo']['username'];
        if (isset($_SESSION['subuser'])) {
            $username = $_SESSION['subuser'];
            $type = 'subuser';
        }
    } elseif ($_SESSION['role'] & ROLE_VMAIL_ACCOUNT) {
        $type = 'email';
        $username = $_SESSION['mailaccount'];
    }
    if (! $type || ! $username) {
        system_failure('cannot get type or username of login');
    }
    db_query(
        "DELETE FROM system.clientcert WHERE id=:id AND type=:type AND username=:username",
        [":id" => $id, ":type" => $type, ":username" => $username]
    );
}

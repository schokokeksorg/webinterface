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

function list_ftpusers()
{
    $uid = (int) $_SESSION['userinfo']['uid'];
    $result = db_query("SELECT id, username, homedir, active, forcessl FROM system.ftpusers WHERE uid=?", [$uid]);
    $ftpusers = [];
    while ($u = $result->fetch()) {
        $ftpusers[] = $u;
    }
    return $ftpusers;
}

function empty_ftpuser()
{
    $myserver = my_server_id();
    return ["id" => "0", "username" => "", "password" => "", "homedir" => "", "active" => "1", "forcessl" => "1", "server" => $myserver];
}

function load_ftpuser($id)
{
    if ($id == 0) {
        return empty_ftpuser();
    }
    $args = [":id" => $id, ":uid" => $_SESSION['userinfo']['uid']];
    $result = db_query("SELECT id, username, password, homedir, active, forcessl, server FROM system.ftpusers WHERE uid=:uid AND id=:id", $args);
    if ($result->rowCount() != 1) {
        system_failure("Fehler beim auslesen des Accounts");
    }
    $account = $result->fetch();
    DEBUG($account);
    return $account;
}


function save_ftpuser($data)
{
    verify_input_username($data['username']);
    if ($data['username'] == '') {
        system_failure('Bitte geben Sie eine Erweiterung für den Benutzernamen an!');
    }
    $homedir = $data['homedir'];
    if (substr($homedir, 0, 1) == '/') {
        $homedir = substr($homedir, 1);
    }
    $homedir = $_SESSION['userinfo']['homedir'] . '/' . $homedir;
    if (!in_homedir($homedir)) {
        system_failure('Pfad scheint nicht in Ihrem Home zu sein oder enthielt ungültige Zeichen.');
    }

    $server = null;
    if ($data['server'] == my_server_id()) {
        $server = null;
    } elseif (in_array($data['server'], additional_servers())) {
        $server = (int) $data['server'];
    }

    $set_password = false;
    $password_hash = '';
    if ($data['password'] != '') {
        $result = strong_password($data['password']);
        if ($result !== true) {
            system_failure("Unsicheres Passwort: " . $result);
        }
        $password_hash = gen_pw_hash($data['password']);
        $set_password = true;
    } elseif (!$data['id']) {
        system_failure('Wenn Sie einen neuen Zugang anlegen, müssen Sie ein Passwort setzen');
    }

    $args = [":username" => $_SESSION['userinfo']['username'] . '-' . $data['username'],
        ":homedir" => $homedir,
        ":active" => ($data['active'] == 1 ? 1 : 0),
        ":forcessl" => ($data['forcessl'] == 0 ? 0 : 1),
        ":server" => $server,
        ":uid" => $_SESSION['userinfo']['uid'], ];

    if ($data['id']) {
        $args[":id"] = $data['id'];
        if ($set_password) {
            $args[':password'] = $password_hash;
            db_query("UPDATE system.ftpusers SET username=:username, password=:password, homedir=:homedir, active=:active, forcessl=:forcessl, server=:server WHERE id=:id AND uid=:uid", $args);
        } else {
            db_query("UPDATE system.ftpusers SET username=:username, homedir=:homedir, active=:active, forcessl=:forcessl, server=:server WHERE id=:id AND uid=:uid", $args);
        }
    } else {
        $args[':password'] = $password_hash;
        db_query("INSERT INTO system.ftpusers (username, password, homedir, uid, active, forcessl, server) VALUES (:username, :password, :homedir, :uid, :active, :forcessl, :server)", $args);
    }
}


function delete_ftpuser($id)
{
    $args = [":id" => $id, ":uid" => $_SESSION['userinfo']['uid']];
    db_query("DELETE FROM system.ftpusers WHERE id=:id AND uid=:uid", $args);
}


function get_gid($groupname)
{
    $result = db_query("SELECT gid FROM system.gruppen WHERE name=?", [$groupname]);
    if ($result->rowCount() != 1) {
        system_failure('cannot determine gid of ftpusers group');
    }
    $a = $result->fetch();
    $gid = (int) $a['gid'];
    if ($gid == 0) {
        system_failure('error on determining gid of ftpusers group');
    }
    return $gid;
}


function have_regular_ftp()
{
    $args = [":gid" => get_gid('ftpusers'), ":uid" => $_SESSION['userinfo']['uid']];
    $result = db_query("SELECT * FROM system.gruppenzugehoerigkeit WHERE gid=:gid AND uid=:uid", $args);
    return ($result->rowCount() > 0);
}


function enable_regular_ftp()
{
    require_role(ROLE_SYSTEMUSER);
    $args = [":gid" => get_gid('ftpusers'), ":uid" => $_SESSION['userinfo']['uid']];
    db_query("REPLACE INTO system.gruppenzugehoerigkeit (gid, uid) VALUES (:gid, :uid)", $args);
}

function disable_regular_ftp()
{
    $args = [":gid" => get_gid('ftpusers'), ":uid" => $_SESSION['userinfo']['uid']];
    db_query("DELETE FROM system.gruppenzugehoerigkeit WHERE gid=:gid AND uid=:uid", $args);
}

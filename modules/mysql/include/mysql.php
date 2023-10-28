<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

function get_mysql_accounts($UID)
{
    $result = db_query("SELECT id, username, description, created FROM misc.mysql_accounts WHERE useraccount=? ORDER BY username", [$UID]);
    if ($result->rowCount() == 0) {
        return [];
    }
    $list = [];
    while ($item = $result->fetch()) {
        $list[] = $item;
    }
    return $list;
}

function get_mysql_databases($UID)
{
    $result = db_query("SELECT id, name, description, created FROM misc.mysql_database WHERE useraccount=? ORDER BY name", [$UID]);
    if ($result->rowCount() == 0) {
        return [];
    }
    $list = [];
    while ($item = $result->fetch()) {
        $list[] = $item;
    }
    return $list;
}

function set_database_description($dbname, $description)
{
    $dbs = get_mysql_databases($_SESSION['userinfo']['uid']);
    $thisdb = null;
    foreach ($dbs as $db) {
        if ($db['name'] == $dbname) {
            $thisdb = $db;
        }
    }
    if ($thisdb == null) {
        system_failure('Ungültige Datenbank');
    }
    $args = [":id" => $thisdb['id'],
                ":desc" => filter_input_oneline($description), ];
    db_query("UPDATE misc.mysql_database SET description=:desc WHERE id=:id", $args);
}

function set_dbuser_description($username, $description)
{
    $users = get_mysql_accounts($_SESSION['userinfo']['uid']);
    $thisuser = null;
    foreach ($users as $user) {
        if ($user['username'] == $username) {
            $thisuser = $user;
        }
    }
    if ($thisuser == null) {
        system_failure('Ungültiger Benutzer');
    }
    $args = [":id" => $thisuser['id'],
                ":desc" => filter_input_oneline($description), ];
    db_query("UPDATE misc.mysql_accounts SET description=:desc WHERE id=:id", $args);
}

function servers_for_databases()
{
    $uid = (int) $_SESSION['userinfo']['uid'];
    $result = db_query("SELECT db.name AS db, hostname FROM misc.mysql_database AS db LEFT JOIN system.useraccounts AS u ON (db.useraccount=u.uid) LEFT JOIN system.servers ON (COALESCE(db.server, u.server) = servers.id) WHERE db.useraccount=?", [$uid]);
    $ret = [];
    while ($line = $result->fetch()) {
        $ret[$line['db']] = $line['hostname'];
    }
    DEBUG($ret);
    return $ret;
}


function get_mysql_access($db, $account)
{
    $uid = $_SESSION['userinfo']['uid'];
    global $mysql_access;
    if (!is_array($mysql_access)) {
        $mysql_access = [];
        $result = db_query("SELECT db.name AS db, acc.username AS user FROM misc.mysql_access AS access LEFT JOIN misc.mysql_database AS db ON (db.id=access.database) LEFT JOIN misc.mysql_accounts AS acc ON (acc.id = access.user) WHERE acc.useraccount=:uid OR db.useraccount=:uid", [":uid" => $uid]);
        if ($result->rowCount() == 0) {
            return false;
        }
        while ($line = $result->fetch(PDO::FETCH_OBJ)) {
            $mysql_access[$line->db][$line->user] = true;
        }
    }
    return (array_key_exists($db, $mysql_access) && array_key_exists($account, $mysql_access[$db]));
}


function set_mysql_access($db, $account, $status)
{
    $uid = $_SESSION['userinfo']['uid'];
    DEBUG("User »{$account}« soll ".($status ? "" : "NICHT ")."auf die Datenbank »{$db}« zugreifen");
    $query = '';
    if ($status) {
        if (get_mysql_access($db, $account)) {
            return null;
        }
        $args = [":db" => $db, ":uid" => $uid];
        $result = db_query("SELECT id FROM misc.mysql_database WHERE name=:db AND useraccount=:uid", $args);
        if ($result->rowCount() != 1) {
            logger(LOG_ERR, "modules/mysql/include/mysql", "mysql", "cannot find database {$db}");
            system_failure("cannot find database »{$db}«");
        }
        $args = [":account" => $account, ":uid" => $uid];
        $result = db_query("SELECT id FROM misc.mysql_accounts WHERE username=:account AND useraccount=:uid", $args);
        if ($result->rowCount() != 1) {
            logger(LOG_ERR, "modules/mysql/include/mysql", "mysql", "cannot find user {$account}");
            system_failure("cannot find database user »{$account}«");
        }
        $args = [":db" => $db, ":uid" => $uid, ":account" => $account];
        db_query("INSERT INTO misc.mysql_access (`database`,user) VALUES ((SELECT id FROM misc.mysql_database WHERE name=:db AND useraccount=:uid LIMIT 1), (SELECT id FROM misc.mysql_accounts WHERE username=:account AND useraccount=:uid))", $args);
        logger(LOG_INFO, "modules/mysql/include/mysql", "mysql", "granting access on »{$db}« to »{$account}«");
    } else {
        if (!get_mysql_access($db, $account)) {
            return null;
        }
        $args = [":db" => $db, ":account" => $account, ":uid" => $uid];
        db_query("DELETE FROM misc.mysql_access WHERE `database`=(SELECT id FROM misc.mysql_database WHERE name=:db AND useraccount=:uid LIMIT 1) AND user=(SELECT id FROM misc.mysql_accounts WHERE username=:account AND useraccount=:uid)", $args);
        logger(LOG_INFO, "modules/mysql/include/mysql", "mysql", "revoking access on »{$db}« from »{$account}«");
    }
}


function create_mysql_account($username, $description = '')
{
    if (!validate_mysql_username($username)) {
        logger(LOG_WARNING, "modules/mysql/include/mysql", "mysql", "illegal username »{$username}«");
        system_failure("Der eingegebene Benutzername entspricht leider nicht der Konvention. Bitte tragen Sie einen passenden Namen ein.");
        return null;
    }
    $args = [":uid" => $_SESSION['userinfo']['uid'],
                ":username" => $username,
                ":desc" => $description, ];
    logger(LOG_INFO, "modules/mysql/include/mysql", "mysql", "creating user »{$username}«");
    db_query("INSERT INTO misc.mysql_accounts (username, password, useraccount, description) VALUES (:username, '!', :uid, :desc)", $args);
}


function delete_mysql_account($username)
{
    $args = [":uid" => $_SESSION['userinfo']['uid'],
                ":username" => $username, ];
    logger(LOG_INFO, "modules/mysql/include/mysql", "mysql", "deleting user »{$username}«");
    db_query("DELETE FROM misc.mysql_accounts WHERE username=:username AND useraccount=:uid", $args);
}


function create_mysql_database($dbname, $description = null, $server = null)
{
    if (!validate_mysql_dbname($dbname)) {
        logger(LOG_WARNING, "modules/mysql/include/mysql", "mysql", "illegal db-name »{$dbname}«");
        system_failure("Der eingegebene Datenbankname entspricht leider nicht der Konvention. Bitte tragen Sie einen passenden Namen ein.");
        return null;
    }
    if (!in_array($server, additional_servers()) || ($server == my_server_id())) {
        $server = null;
    }
    $args = [":dbname" => $dbname,
                ":uid" => $_SESSION['userinfo']['uid'],
                ":desc" => $description,
                ":server" => $server, ];
    logger(LOG_INFO, "modules/mysql/include/mysql", "mysql", "creating database »{$dbname}«");
    db_query("INSERT INTO misc.mysql_database (name, useraccount, server, description) VALUES (:dbname, :uid, :server, :desc)", $args);
}


function delete_mysql_database($dbname)
{
    $args = [":dbname" => $dbname,
                ":uid" => $_SESSION['userinfo']['uid'], ];
    logger(LOG_INFO, "modules/mysql/include/mysql", "mysql", "removing database »{$dbname}«");
    db_query("DELETE FROM misc.mysql_database WHERE name=:dbname AND useraccount=:uid", $args);
}


function validate_mysql_dbname($dbname)
{
    $sys_username = $_SESSION['userinfo']['username'];
    return preg_match("/^{$sys_username}(_[a-zA-Z0-9_-]+)?$/", $dbname);
}


function validate_mysql_username($username)
{
    return validate_mysql_dbname($username) && (strlen($username) <= 16);
}



function set_mysql_password($username, $password)
{
    $args = [":uid" => $_SESSION['userinfo']['uid'],
                ":username" => $username,
                ":password" => $password, ];
    logger(LOG_INFO, "modules/mysql/include/mysql", "mysql", "updating password for »{$username}«");
    db_query("UPDATE misc.mysql_accounts SET password=PASSWORD(:password) WHERE username=:username AND useraccount=:uid", $args);
}


function has_mysql_database($dbname)
{
    $args = [":uid" => $_SESSION['userinfo']['uid'],
                ":dbname" => $dbname, ];
    $result = db_query("SELECT NULL FROM misc.mysql_database WHERE name=:dbname AND useraccount=:uid", $args);
    return ($result->rowCount() == 1);
}


function has_mysql_user($username)
{
    $args = [":uid" => $_SESSION['userinfo']['uid'],
                ":username" => $username, ];
    $result = db_query("SELECT NULL FROM misc.mysql_accounts WHERE username=:username AND useraccount=:uid", $args);
    return ($result->rowCount() == 1);
}

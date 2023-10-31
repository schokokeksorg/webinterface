<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_role(ROLE_SYSTEMUSER);
require_once("inc/base.php");
require_once("inc/security.php");
require_once("inc/debug.php");


function list_subusers()
{
    $uid = (int) $_SESSION['userinfo']['uid'];
    $result = db_query("SELECT id, username, modules FROM system.subusers WHERE uid=?", [$uid]);
    $subusers = [];
    while ($item = $result->fetch()) {
        $item['modules'] = explode(',', $item['modules']);
        $subusers[] = $item;
    }
    DEBUG($subusers);
    return $subusers;
}


function load_subuser($id)
{
    $args = [":id" => $id, ":uid" => $_SESSION['userinfo']['uid']];

    $result = db_query("SELECT id, username, modules FROM system.subusers WHERE uid=:uid AND id=:id", $args);
    $item = $result->fetch();
    $item['modules'] = explode(',', $item['modules']);
    return $item;
}


function available_modules()
{
    $modules = [];
    $allmodules = get_modules_info();

    // Das su-Modul ist hierfuer unwichtig
    unset($allmodules['su']);

    foreach ($allmodules as $modname => $modinfo) {
        if (isset($modinfo['permission'])) {
            $modules[$modname] = $modinfo['permission'];
        }
    }
    return $modules;
}

function delete_subuser($id)
{
    $args = [":id" => $id, ":uid" => $_SESSION['userinfo']['uid']];

    db_query("DELETE FROM system.subusers WHERE id=:id AND uid=:uid", $args);
}

function empty_subuser()
{
    $subuser = ["id" => null,
                   "username" => $_SESSION['userinfo']['username'] . '_',
                   "modules" => ['index'], ];
    return $subuser;
}

function new_subuser($username, $requested_modules, $password)
{
    $username = filter_input_username($username);
    if (strpos($username, $_SESSION['userinfo']['username']) !== 0) {
        // Username nicht enthalten (FALSE) oder nicht am Anfang (>0)
        system_failure("Ungültiger Benutzername!");
    }

    if (!is_array($requested_modules)) {
        system_failure("Module nicht als array erhalten!");
    }
    DEBUG($requested_modules);
    $allmods = available_modules();
    $modules = [];
    foreach ($requested_modules as $mod) {
        if (isset($allmods[$mod])) {
            $modules[] = $mod;
        }
    }
    DEBUG($modules);
    if (count($modules) == 0) {
        system_failure("Es sind (nach der Filterung) keine Module mehr übrig!");
    }

    $result = strong_password($password);
    if ($result !== true) {
        system_failure("Unsicheres Passwort: " . $result);
    }

    $args = [":uid" => $_SESSION['userinfo']['uid'],
                ":username" => $username,
                ":password" => hash("sha256", $password),
                ":modules" => implode(',', $modules), ];

    db_query("INSERT INTO system.subusers (uid, username, password, modules) VALUES (:uid, :username, :password, :modules)", $args);
}


function edit_subuser($id, $username, $requested_modules, $password)
{
    $uid = (int) $_SESSION['userinfo']['uid'];

    $id = (int) $id;
    $my_subusers = list_subusers();
    $valid = false;
    foreach ($my_subusers as $x) {
        if ($x['id'] == $id) {
            $valid = true;
        }
    }
    if (!$valid) {
        system_failure("Kann diesen Account nicht finden!");
    }

    $username = filter_input_username($username);
    if (strpos($username, $_SESSION['userinfo']['username']) !== 0) {
        // Username nicht enthalten (FALSE) oder nicht am Anfang (>0)
        system_failure("Ungültiger Benutzername!");
    }


    if (!is_array($requested_modules)) {
        system_failure("Module nicht als array erhalten!");
    }
    $allmods = available_modules();
    $modules = [];
    foreach ($requested_modules as $mod) {
        if (isset($allmods[$mod])) {
            $modules[] = $mod;
        }
    }
    if (count($modules) == 0) {
        system_failure("Es sind (nach der Filterung) keine Module mehr übrig!");
    }

    $args = [":uid" => $_SESSION['userinfo']['uid'],
                ":id" => $id,
                ":username" => $username,
                ":modules" => implode(',', $modules), ];

    $pwchange = '';
    if ($password) {
        $result = strong_password($password);
        if ($result !== true) {
            system_failure("Unsicheres Passwort: " . $result);
        }
        $args[':password'] = hash("sha256", $password);
        $pwchange = ", password=:password";
    }


    db_query("UPDATE system.subusers SET username=:username, modules=:modules{$pwchange} WHERE id=:id AND uid=:uid", $args);
}

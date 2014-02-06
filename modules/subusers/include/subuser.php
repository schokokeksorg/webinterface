<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2013 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_role(ROLE_SYSTEMUSER);
require_once("inc/base.php");
require_once("inc/security.php");
require_once("inc/debug.php");


function list_subusers()
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT id, username, modules FROM system.subusers WHERE uid=?", array($uid));
  $subusers = array();
  while ($item = $result->fetch())
  {
    $item['modules'] = explode(',', $item['modules']);
    $subusers[] = $item;
  }
  DEBUG($subusers);
  return $subusers;
}


function load_subuser($id) {
  $args = array(":id" => $id, ":uid" => $_SESSION['userinfo']['uid']);
  
  $result = db_query("SELECT id, username, modules FROM system.subusers WHERE uid=:uid AND id=:id", $args);
  $item = $result->fetch();
  $item['modules'] = explode(',', $item['modules']);
  return $item;
}


function available_modules()
{
  $modules = array();
  $allmodules = get_modules_info();

  // Das su-Modul ist hierfuer unwichtig
  unset($allmodules['su']);

  foreach ($allmodules as $modname => $modinfo)
  {
    if (isset($modinfo['permission']))
      $modules[$modname] = $modinfo['permission'];
  }
  return $modules;
}

function delete_subuser($id) {
  $args = array(":id" => $id, ":uid" => $_SESSION['userinfo']['uid']);
  
  db_query("DELETE FROM system.subusers WHERE id=:id AND uid=:uid", $args);
}

function empty_subuser()
{
  $subuser = array("id" => NULL, 
                   "username" => $_SESSION['userinfo']['username'].'_', 
                   "modules" => array('index'));
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
  $modules = array();
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
    system_failure("Unsicheres Passwort. Die Meldung von cracklib lautet: ".$result);
  }

  $args = array(":uid" => $_SESSION['userinfo']['uid'],
                ":username" => $username,
                ":password" => hash("sha256", $password),
                ":modules" => implode(',', $modules));

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
  $modules = array();
  foreach ($requested_modules as $mod) {
    if (isset($allmods[$mod])) {
      $modules[] = $mod;
    }
  }
  if (count($modules) == 0) {
    system_failure("Es sind (nach der Filterung) keine Module mehr übrig!");
  }
  
  $args = array(":uid" => $_SESSION['userinfo']['uid'],
                ":id" => $id,
                ":username" => $username,
                ":modules" => implode(',', $modules));

  $pwchange = '';
  if ($password) {
    $result = strong_password($password);
    if ($result !== true) {
      system_failure("Unsicheres Passwort. Die Meldung von cracklib lautet: ".$result);
    }
    $args[':password'] = hash("sha256", $password);
    $pwchange = ", password=:password";
  }


  db_query("UPDATE system.subusers SET username=:username, modules=:modules{$pwchange} WHERE id=:id AND uid=:uid", $args);
}







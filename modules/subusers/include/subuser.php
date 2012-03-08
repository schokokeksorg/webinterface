<?php
require_role(ROLE_SYSTEMUSER | ROLE_CUSTOMER);
require_once("inc/base.php");
require_once("inc/security.php");
require_once("inc/debug.php");


function list_subusers()
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT id, username, modules FROM system.subusers WHERE uid={$uid}");
  $subusers = array();
  while ($item = mysql_fetch_assoc($result))
  {
    $item['modules'] = explode(',', $item['modules']);
    $subusers[] = $item;
  }
  DEBUG($subusers);
  return $subusers;
}


function load_subuser($id) {
  $id = (int) $id;
  $uid = (int) $_SESSION['userinfo']['uid'];
  
  $result = db_query("SELECT id, username, modules FROM system.subusers WHERE uid={$uid} AND id={$id}");
  $item = mysql_fetch_assoc($result);
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
  $id = (int) $id;
  $uid = (int) $_SESSION['userinfo']['uid'];
  
  db_query("DELETE FROM system.subusers WHERE id={$id} AND uid={$uid}");
}

function empty_subuser()
{
  $subuser = array("id" => NULL, "username" => $_SESSION['userinfo']['username'].'_', "modules" => array('index'));
  return $subuser;
}

function new_subuser($username, $requested_modules, $password) 
{
  $uid = (int) $_SESSION['userinfo']['uid'];

  $username = mysql_real_escape_string(filter_input_username($username));
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
  $modules = mysql_real_escape_string(implode(',', $modules));
  
  $result = strong_password($password);
  if ($result !== true) {
    system_failure("Unsicheres Passwort. Die Meldung von cracklib lautet: ".$result);
  }
  $password = hash("sha256", $password);

  db_query("INSERT INTO system.subusers (uid, username, password, modules) VALUES ({$uid}, '{$username}', '{$password}', '{$modules}')");
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

  $username = mysql_real_escape_string(filter_input_username($username));
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
  $modules = mysql_real_escape_string(implode(',', $modules));
  
  $pwchange = '';
  if ($password) {
    $result = strong_password($password);
    if ($result !== true) {
      system_failure("Unsicheres Passwort. Die Meldung von cracklib lautet: ".$result);
    }
    $password = hash("sha256", $password);
    $pwchange = ", password='{$password}'";
  }


  db_query("UPDATE system.subusers SET username='{$username}', modules='{$modules}'{$pwchange} WHERE id={$id} AND uid={$uid}");
}







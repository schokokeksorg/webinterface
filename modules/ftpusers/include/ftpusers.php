<?php

require_once('inc/base.php');

function list_ftpusers()
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT id, username, homedir, active FROM system.ftpusers WHERE uid=$uid");
  $ftpusers = array();
  while ($u = mysql_fetch_assoc($result)) {
    $ftpusers[] = $u;
  }
  return $ftpusers;
}

function empty_ftpuser()
{
  $myserver = my_server_id();
  return array("id" => "0", "username" => "", "password" => "", "homedir" => "", "active" => "1", "server" => $myserver);
}

function load_ftpuser($id)
{
  if ($id == 0)
    return empty_ftpuser();
  $uid = (int) $_SESSION['userinfo']['uid'];
  $id = (int) $id;
  $result = db_query("SELECT id, username, password, homedir, active, server FROM system.ftpusers WHERE uid={$uid} AND id='{$id}' LIMIT 1");
  if (mysql_num_rows($result) != 1)
    system_failure("Fehler beim auslesen des Accounts");
  $account = mysql_fetch_assoc($result);
  DEBUG($account);
  return $account;
}


function save_ftpuser($data)
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $id = (int) $data['id'];
  verify_input_username($data['username']);
  if ($data['username'] == '')
    system_failure('Bitte geben Sie eine Erweiterung für den Benutzernamen an!');
  $username = $_SESSION['userinfo']['username'].'-'.$data['username'];
  $homedir = filter_input_general($data['homedir']);
  if (substr($homedir, 0, 1) == '/')
    $homedir = substr($homedir, 1);
  $homedir = $_SESSION['userinfo']['homedir'].'/'.$homedir;
  if (! in_homedir($homedir))
    system_failure('Pfad scheint nicht in Ihrem Home zu sein oder enthielt ungültige Zeichen.');
  $active = ($data['active'] == 1 ? '1' : '0');

  $server = NULL;
  if ($data['server'] == my_server_id())
  {
    $server = NULL;
  }
  elseif (in_array($data['server'], additional_servers()))
  {
    $server = (int) $data['server'];
  }
  $server = maybe_null($server);

  $password_query = '';
  $password_hash = '';
  if ($data['password'] != '')
  {
    if (defined("CRYPT_SHA512") && CRYPT_SHA512 == 1)
    {
      $rounds = rand(1000, 5000);
      $salt = "rounds=".$rounds."$".random_string(8);
      $password_hash = crypt($data['password'], "\$6\${$salt}\$");
    }
    else
    {
      $salt = random_string(8);
      $password_hash = crypt($data['password'], "\$1\${$salt}\$");
    }
    $password_query = "password='{$password_hash}', ";
  }
  elseif (! $id)
  {
    system_failure('Wenn Sie einen neuen Zugang anlegen, müssen Sie ein Passwort setzen');
  }
    
  
  if ($id)
    db_query("UPDATE system.ftpusers SET username='{$username}', {$password_query} homedir='{$homedir}', active='{$active}', server={$server} WHERE id={$id} AND uid={$uid} LIMIT 1");
  else
    db_query("INSERT INTO system.ftpusers (username, password, homedir, uid, active, server) VALUES ('{$username}', '{$password_hash}', '{$homedir}', '{$uid}', '{$active}', {$server})");
}


function delete_ftpuser($id)
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $id = (int) $id;
  db_query("DELETE FROM system.ftpusers WHERE id='{$id}' AND uid={$uid} LIMIT 1");
}


function get_gid($groupname)
{
  $groupname = mysql_real_escape_string($groupname);
  $result = db_query("SELECT gid FROM system.gruppen WHERE name='{$groupname}' LIMIT 1");
  if (mysql_num_rows($result) != 1)
    system_failure('cannot determine gid of ftpusers group');
  $a = mysql_fetch_assoc($result);
  $gid = (int) $a['gid'];
  if ($gid == 0)
    system_failure('error on determining gid of ftpusers group');
  return $gid;
}


function have_regular_ftp()
{
  $gid = get_gid('ftpusers');
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT * FROM system.gruppenzugehoerigkeit WHERE gid='$gid' AND uid='$uid'");
  return (mysql_num_rows($result) > 0);
}


function enable_regular_ftp()
{
  require_role(ROLE_SYSTEMUSER);
  $gid = get_gid('ftpusers');
  $uid = (int) $_SESSION['userinfo']['uid'];
  db_query("REPLACE INTO system.gruppenzugehoerigkeit (gid, uid) VALUES ('$gid', '$uid')");
}

function disable_regular_ftp()
{
  $gid = get_gid('ftpusers');
  $uid = (int) $_SESSION['userinfo']['uid'];
  db_query("DELETE FROM system.gruppenzugehoerigkeit WHERE gid='$gid' AND uid='$uid'");
}




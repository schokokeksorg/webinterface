<?php

function get_mysql_accounts($UID)
{
  $UID = (int) $UID;
  $result = db_query("SELECT username FROM misc.mysql_accounts WHERE useraccount=$UID");
  if (mysql_num_rows($result) == 0)
    return array();
  $list = array();
  while ($item = mysql_fetch_object($result))
  {
    array_push($list, $item->username);
  }
  return $list;
}

function get_mysql_databases($UID)
{
  $UID = (int) $UID;
  $result = db_query("SELECT name FROM misc.mysql_database WHERE useraccount=$UID");
  if (mysql_num_rows($result) == 0)
    return array();
  $list = array();
  while ($item = mysql_fetch_object($result))
  {
    array_push($list, $item->name);
  }
  return $list;
}


function get_mysql_access($db, $account)
{
  $uid = $_SESSION['userinfo']['uid'];
  global $mysql_access;
  if (!is_array($mysql_access))
  {
    $mysql_access = array();
    $result = db_query("SELECT db.name AS db, acc.username AS user FROM misc.mysql_access AS access LEFT JOIN misc.mysql_database AS db ON (db.id=access.database) LEFT JOIN misc.mysql_accounts AS acc ON (acc.id = access.user) WHERE acc.useraccount={$uid} OR db.useraccount={$uid};");
    if (mysql_num_rows($result) == 0)
      return false;
    while ($line = mysql_fetch_object($result))
      $mysql_access[$line->db][$line->user] = true;
  }
  return (array_key_exists($db, $mysql_access) && array_key_exists($account, $mysql_access[$db]));
}


function set_mysql_access($db, $account, $status)
{
  $uid = $_SESSION['userinfo']['uid'];
  $db = mysql_real_escape_string($db);
  $account = mysql_real_escape_string($account);
  $query = '';
  if ($status)
  {
    if (get_mysql_access($db, $account))
      return NULL;
    $query = "INSERT INTO misc.mysql_access (`database`,user) VALUES ((SELECT id FROM misc.mysql_database WHERE name='{$db}' AND useraccount={$uid} LIMIT 1), (SELECT id FROM misc.mysql_accounts WHERE username='{$account}' AND useraccount={$uid}));";
    logger("modules/mysql/include/mysql", "mysql", "granting access on »{$db}« to »{$account}«");
  }
  else
  {
    if (! get_mysql_access($db, $account))
      return NULL;
    $query = "DELETE FROM misc.mysql_access WHERE `database`=(SELECT id FROM misc.mysql_database WHERE name='{$db}' AND useraccount={$uid} LIMIT 1) AND user=(SELECT id FROM misc.mysql_accounts WHERE username='{$account}' AND useraccount={$uid});";
    logger("modules/mysql/include/mysql", "mysql", "revoking access on »{$db}« from »{$account}«");
  }
  db_query($query);
}


function create_mysql_account($username)
{
  if (! validate_mysql_dbname($username))
  {
    logger("modules/mysql/include/mysql", "mysql", "illegal username »{$username}«");
    input_error("Der eingegebene Benutzername entspricht leider nicht der Konvention. Bitte tragen Sie einen passenden Namen ein.");
    return NULL;
  }
  if (strlen($username) > 16)
  {
    warning('Der eingegebene MySQL-Benutzername wurde abgeschnitten. Systemseitig begrenzt MySQL einen Benutzernamen auf 16 Zeichen.');
  }
  $uid = $_SESSION['userinfo']['uid'];
  $username = mysql_real_escape_string($username);
  logger("modules/mysql/include/mysql", "mysql", "creating user »{$username}«");
  db_query("INSERT INTO misc.mysql_accounts (username, password, useraccount) VALUES ('$username', '!', $uid);");
}


function delete_mysql_account($username)
{
  $username = mysql_real_escape_string($username);
  $uid = $_SESSION['userinfo']['uid'];
  logger("modules/mysql/include/mysql", "mysql", "deleting user »{$username}«");
  db_query("DELETE FROM misc.mysql_accounts WHERE username='{$username}' AND useraccount='{$uid}' LIMIT 1;");
}


function create_mysql_database($dbname)
{
  if (! validate_mysql_dbname($dbname))
  {
    logger("modules/mysql/include/mysql", "mysql", "illegal db-name »{$dbname}«");
    input_error("Der eingegebene Datenbankname entspricht leider nicht der Konvention. Bitte tragen Sie einen passenden Namen ein.");
    return NULL;
  }
  $dbname = mysql_real_escape_string($dbname);
  $uid = $_SESSION['userinfo']['uid'];
  logger("modules/mysql/include/mysql", "mysql", "creating database »{$dbname}«");
  db_query("INSERT INTO misc.mysql_database (name, useraccount) VALUES ('$dbname', $uid);");
}


function delete_mysql_database($dbname)
{
  $dbname = mysql_real_escape_string($dbname);
  $uid = $_SESSION['userinfo']['uid'];
  logger("modules/mysql/include/mysql", "mysql", "removing database »{$dbname}«");
  db_query("DELETE FROM misc.mysql_database WHERE name='{$dbname}' AND useraccount='{$uid}' LIMIT 1;");
}


function validate_mysql_username($username)
{
  $sys_username = $_SESSION['userinfo']['username'];
  return preg_match("/^{$sys_username}(_[a-zA-Z0-9_-]+)?$/", $username);
}


function validate_mysql_dbname($dbname)
{
  // Funktioniert! ;-)
  return validate_mysql_username($dbname);
}



function set_mysql_password($username, $password)
{
  $username = mysql_real_escape_string($username);
  $password = mysql_real_escape_string($password);
  $uid = $_SESSION['userinfo']['uid'];
  logger("modules/mysql/include/mysql", "mysql", "updating password for »{$username}«");
  db_query("UPDATE misc.mysql_accounts SET password=PASSWORD('$password') WHERE username='$username' AND useraccount=$uid;");
}


function has_mysql_database($dbname)
{
  $uid = $_SESSION['userinfo']['uid'];
  $dbname = mysql_real_escape_string($dbname);
  $result = db_query("SELECT NULL FROM misc.mysql_database WHERE name='{$dbname}' AND useraccount='{$uid}' LIMIT 1;");
  return (mysql_num_rows($result) == 1);
}


function has_mysql_user($username)
{
  $uid = $_SESSION['userinfo']['uid'];
  $userame = mysql_real_escape_string($username);
  $result = db_query("SELECT NULL FROM misc.mysql_accounts WHERE username='{$username}' AND useraccount='{$uid}' LIMIT 1;");
  return (mysql_num_rows($result) == 1);
}


?>

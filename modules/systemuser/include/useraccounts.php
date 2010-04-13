<?php

require_once("inc/debug.php");
require_once("inc/db_connect.php");



function customer_may_have_useraccounts()
{
  $customerno = (int) $_SESSION['customerinfo']['customerno'];
  $result = db_query("SELECT COUNT(*) FROM system.useraccounts WHERE kunde={$customerno}");
  return (mysql_num_rows($result) > 0);
}

function customer_useraccount($uid) {
  $uid = (int) $uid;
  $customerno = (int) $_SESSION['customerinfo']['customerno'];
  $result = db_query("SELECT 1 FROM system.useraccounts WHERE kunde={$customerno} AND uid={$uid} AND kundenaccount=1");
  return mysql_num_rows($result) > 0;
}

function primary_useraccount()
{
  if (! ($_SESSION['role'] & ROLE_SYSTEMUSER))
    return NULL;
  $customerno = (int) $_SESSION['customerinfo']['customerno'];
  $result = db_query("SELECT MIN(uid) AS uid FROM system.useraccounts WHERE kunde={$customerno}");
  $uid = mysql_fetch_object($result)->uid;
  DEBUG("primary useraccount: {$uid}");
  return $uid;
}


function available_shells()
{
  $result = db_query("SELECT path, name FROM system.shells WHERE usable=1");
  $ret = array();
  while ($s = mysql_fetch_assoc($result))
  {
    $ret[$s['path']] = $s['name'];
  }
  DEBUG($ret);
  return $ret;
}


function list_useraccounts()
{
  $customerno = (int) $_SESSION['customerinfo']['customerno'];
  $result = db_query("SELECT uid,username,name,erstellungsdatum,quota,shell FROM system.useraccounts WHERE kunde={$customerno}");
  $ret = array();
  while ($item = mysql_fetch_assoc($result))
  {
    array_push($ret, $item);
  }
  DEBUG($ret);
  return $ret;
}


function get_account_details($uid, $customerno=0)
{
  $uid = (int) $uid;
  $customerno = (int) $customerno;
  if ($customerno == 0)
    $customerno = $_SESSION['customerinfo']['customerno'];
  $result = db_query("SELECT uid,username,name,shell,quota,erstellungsdatum FROM system.useraccounts WHERE kunde={$customerno} AND uid={$uid}");
  if (mysql_num_rows($result) == 0)
    system_failure("Cannot find the requestes useraccount (for this customer).");
  return mysql_fetch_assoc($result);
}

function get_used_quota($uid)
{
  $uid = (int) $uid;
  $result = db_query("SELECT s.hostname AS server, used, COALESCE(us.quota, u.quota) AS quota FROM system.usedquota AS uq LEFT JOIN system.useraccounts AS u USING (uid) LEFT JOIN system.servers AS s ON (s.id=uq.server) LEFT JOIN system.user_server AS us ON (us.uid=uq.uid AND us.server=uq.server) WHERE uq.uid='{$uid}'");
  $ret = array();
  while ($line = mysql_fetch_assoc($result))
    $ret[] = $line;
  DEBUG($ret);
  return $ret;
}


function set_account_details($account)
{
  $uid = (int) $account['uid'];
  $customerno = NULL;
  if ($_SESSION['role'] & ROLE_CUSTOMER)
    $customerno = (int) $_SESSION['customerinfo']['customerno'];
  else
    $customerno = (int) $_SESSION['userinfo']['customerno'];

  $fullname = maybe_null(mysql_real_escape_string(filter_input_general($account['name'])));
  $shell = mysql_real_escape_string(filter_input_general($account['shell']));
  $quota = (int) $account['quota'];

  db_query("UPDATE system.useraccounts SET name={$fullname}, quota={$quota}, shell='{$shell}' WHERE kunde={$customerno} AND uid={$uid}");
  logger(LOG_INFO, "modules/systemuser/include/useraccounts", "systemuser", "updated details for uid {$uid}");

}

function get_customer_quota()
{
  $cid = (int) $_SESSION['customerinfo']['customerno'];
  $result = db_query("SELECT SUM(u.quota) AS assigned, cq.quota AS max FROM system.customerquota AS cq INNER JOIN system.useraccounts AS u ON (u.kunde=cq.cid) WHERE cq.cid={$cid}");
  $ret = mysql_fetch_assoc($result);
  DEBUG($ret);
  return $ret;
}


?>

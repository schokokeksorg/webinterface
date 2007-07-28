<?php

require_once("inc/debug.php");
require_once("inc/db_connect.php");

require_role(ROLE_CUSTOMER);


function customer_may_have_useraccounts()
{
  $customerno = (int) $_SESSION['customerinfo']['customerno'];
  $result = db_query("SELECT COUNT(*) FROM system.useraccounts WHERE kunde={$customerno}");
  return (mysql_num_rows($result) > 0);
}



function list_useraccounts()
{
  $customerno = (int) $_SESSION['customerinfo']['customerno'];
  $result = db_query("SELECT uid,username,name,erstellungsdatum,softquota FROM system.useraccounts WHERE kunde={$customerno}");
  $ret = array();
  while ($item = mysql_fetch_object($result))
  {
    DEBUG('Useraccount: '.print_r($item, true));
    array_push($ret, $item);
  }
  return $ret;
}


function get_account_details($uid)
{
  $uid = (int) $uid;
  $customerno = (int) $_SESSION['customerinfo']['customerno'];
  $result = db_query("SELECT uid,username,name,softquota FROM system.useraccounts WHERE kunde={$customerno} AND uid={$uid}");
  if (mysql_num_rows($result) == 0)
    system_failure("Cannot find the requestes useraccount (for this customer).");
  return mysql_fetch_array($result);
}



function set_systemuser_details($uid, $fullname, $quota)
{
  $uid = (int) $uid;
  $customerno = (int) $_SESSION['customerinfo']['customerno'];
  $fullname = maybe_null(mysql_real_escape_string($fullname));
  $quota = (int) $quota;

  db_query("UPDATE system.useraccounts SET name={$fullname} WHERE kunde={$customerno} AND uid={$uid} LIMIT 1");
  logger("modules/systemuser/include/useraccounts.php", "systemuser", "updated real name for uid {$uid}");

}


?>

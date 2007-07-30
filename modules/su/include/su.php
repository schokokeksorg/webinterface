<?php


function list_system_users()
{
  require_role(ROLE_SYSADMIN);

  $result = db_query("SELECT uid,username FROM system.v_useraccounts ORDER BY username");
  
  $ret = array();
  while ($item = mysql_fetch_object($result))
    array_push($ret, $item);
  return $ret;
}


function list_customers()
{
  require_role(ROLE_SYSADMIN);

  $result = db_query("SELECT id, IF(firma IS NULL, CONCAT_WS(' ', vorname, nachname), CONCAT(firma, ' (', CONCAT_WS(' ', vorname, nachname), ')')) AS name FROM kundendaten.kunden");
  
  $ret = array();
  while ($item = mysql_fetch_object($result))
    array_push($ret, $item);
  return $ret;
}


?>

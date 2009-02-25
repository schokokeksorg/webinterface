<?php

require_once('inc/base.php');


function load_results()
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT directory, docroot, lastcheck, appname, version, state, safeversion, vulninfo FROM qatools.freewvs_results WHERE uid={$uid}");
  $ret = array();
  while ($line = mysql_fetch_assoc($result)) {
    array_push($ret, $line);
  }
  return $ret;
}

function get_upgradeinstructions($appname) {
  $appname = mysql_real_escape_string($appname);
  $result = db_query("SELECT url FROM qatools.freewvs_upgradeinstructions WHERE appname='{$appname}' LIMIT 1");
  if (mysql_num_rows($result) > 0) {
    $tmp = mysql_fetch_array($result);
    return $tmp[0];
  }
  return NULL;
}




<?php

require_once("inc/base.php");
require_once("inc/error.php");
require_once("inc/security.php");

require_once('class/domain.php');

function list_vhosts()
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT id,fqdn,docroot,docroot_is_default,php,options FROM vhosts.v_vhost WHERE uid={$uid}");
  $ret = array();
  while ($item = mysql_fetch_assoc($result))
    array_push($ret, $item);
  return $ret;
}


function domainselect($selected = NULL)
{
  global $domainlist;
  if ($domainlist == NULL)
    $domainlist = get_domain_list($_SESSION['customerinfo']['customerno'],
                                  $_SESSION['userinfo']['uid']);
  $selected = (int) $selected;

  $ret = '<select name="domain" size="1">';
  foreach ($domainlist as $dom)
  {
    $s = '';
    if ($selected == $dom->id)
      $s = ' selected="selected" ';
    $ret .= "<option value=\"{$dom->id}\"{$s}>{$dom->fqdn}</option>\n";
  }
  $ret .= '</select>';
  return $ret;
}



function get_vhost_details($id)
{
  $id = (int) $id;
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT * FROM vhosts.v_vhost WHERE uid={$uid} AND id={$id}");
  if (mysql_num_rows($result) != 1)
    system_failure('Interner Fehler beim Auslesen der Daten');

  return mysql_fetch_assoc($result);
}



function get_aliases($vhost)
{
  $vhost = (int) $vhost;
  $result = db_query("SELECT id,fqdn,options FROM vhosts.v_alias WHERE vhost={$vhost}");
  $ret = array();
  while ($item = mysql_fetch_assoc($result))
    array_push($ret, $item);
  return $ret;
}



?>

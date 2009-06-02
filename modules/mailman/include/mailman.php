<?php

require_once('inc/base.php');
require_once('inc/debug.php');
require_once('inc/security.php');


function get_lists()
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT id, status, listname, fqdn, admin FROM mail.v_mailman_lists WHERE owner={$uid};");
  $ret = array();
  while ($list = mysql_fetch_assoc($result))
    $ret[] = $list;
  DEBUG($ret);
  return $ret;
}


function get_list($id)
{
  $id = (int) $id;
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT id, status, listname, fqdn, admin FROM mail.v_mailman_lists WHERE owner={$uid} AND id={$id};");
  if (mysql_num_rows($result) < 1)
    system_failure('Die gewünschte Mailingliste konnte nicht gefunden werden');
  $list = mysql_fetch_assoc($result);
  DEBUG($list);

  return $list;
}


function delete_list($id)
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $id = (int) $id;
  db_query("UPDATE mail.mailman_lists SET status='delete' WHERE owner={$uid} AND id={$id};");
}


function create_list($listname, $maildomain, $admin)
{
  verify_input_username($listname);
  $maildomain = maybe_null( (int) $maildomain );
  $owner = (int) $_SESSION['userinfo']['uid'];
  verify_input_general($admin);
  if (! check_emailaddr($admin))
    system_failure('Der Verwalter muss eine gültige E-Mail-Adresse sein ('.$admin.').');
  $admin = mysql_real_escape_string($admin);
  $result = db_query("SELECT id FROM mail.mailman_lists WHERE listname='{$listname}'");
  if (mysql_num_rows($result) > 0)
    system_failure('Eine Liste mit diesem Namen existiert bereits (unter dieser oder einer anderen Domain). Jeder Listenname kann nur einmal verwendet werden.');

  db_query("INSERT INTO mail.mailman_lists (status, listname, maildomain, owner, admin) VALUES ('pending', '{$listname}', {$maildomain}, {$owner}, '{$admin}');");
  DEBUG('Neue ID: '.mysql_insert_id());
}


function get_mailman_domains()
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT md.id, md.fqdn FROM mail.v_mailman_domains AS md left join mail.v_domains AS d on (d.id=md.domain) where d.user={$uid}");
  $ret = array();
  while ($dom = mysql_fetch_assoc($result))
    $ret[] = $dom;
  DEBUG($ret);
  return $ret;
}


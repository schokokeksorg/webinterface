<?php

require_once('inc/debug.php');
require_once('inc/db_connect.php');
require_once('inc/base.php');
require_once('inc/security.php');

require_once('class/domain.php');

require_once('common.php');

function mailaccounts($uid)
{
  $uid = (int) $uid;
  $result = db_query("SELECT m.id,concat_ws('@',`m`.`local`,if(isnull(`m`.`domain`),_utf8'schokokeks.org',`d`.`domainname`)) AS `account`, `m`.`password` AS `cryptpass`,`m`.`maildir` AS `maildir`,aktiv from (`mail`.`mailaccounts` `m` left join `mail`.`v_domains` `d` on((`d`.`id` = `m`.`domain`))) WHERE m.uid=$uid");
  DEBUG("Found ".@mysql_num_rows($result)." rows!");
  $accounts = array();
  if (@mysql_num_rows($result) > 0)
    while ($acc = @mysql_fetch_object($result))
      array_push($accounts, array('id'=> $acc->id, 'account' => $acc->account, 'mailbox' => $acc->maildir, 'cryptpass' => $acc->cryptpass, 'enabled' => ($acc->aktiv == 1)));
  return $accounts;
}

function get_mailaccount($id)
{
  $uid = (int) $uid;
  $result = db_query("SELECT concat_ws('@',`m`.`local`,if(isnull(`m`.`domain`),_utf8'schokokeks.org',`d`.`domainname`)) AS `account`, `m`.`password` AS `cryptpass`,`m`.`maildir` AS `maildir`,aktiv from (`mail`.`mailaccounts` `m` left join `mail`.`v_domains` `d` on((`d`.`id` = `m`.`domain`))) WHERE m.id=$id");
  DEBUG("Found ".mysql_num_rows($result)." rows!");
  $acc = mysql_fetch_object($result);
  $ret = array('account' => $acc->account, 'mailbox' => $acc->maildir,  'enabled' => ($acc->aktiv == 1));
  DEBUG(print_r($ret, true));
  return $ret;
}

function change_mailaccount($id, $arr)
{
  $id = (int) $id;
  $conditions = array();

  if (isset($arr['account']))
  {
    list($local, $domain) = explode('@', $arr['account'], 2);
    $domain = new Domain( (string) $domain);
    if ($domain->id == NULL)
      array_push($conditions, "domain=NULL");
    else
      array_push($conditions, "domain={$domain->id}");

    array_push($conditions, "local='".mysql_real_escape_string($local)."'");
  }
  if (isset($arr['mailbox']))
    if ($arr['mailbox'] == '')
      array_push($conditions, "`maildir`=NULL");
    else
      array_push($conditions, "`maildir`='".mysql_real_escape_string($arr['mailbox'])."'");

  if (isset($arr['password']))
  {
    $encpw = encrypt_mail_password($arr['password']);
    array_push($conditions, "`password`='$encpw'");
  }

  if (isset($arr['enabled']))
    array_push($conditions, "`aktiv`=".($arr['enabled'] == 'Y' ? "1" : "0"));


  db_query("UPDATE mail.mailaccounts SET ".implode(",", $conditions)." WHERE id='$id' LIMIT 1");
  logger("modules/imap/include/mailaccounts.php", "imap", "updated account »{$arr['account']}«");

}

function create_mailaccount($arr)
{
  $values = array();

  if (($arr['account']) == '')
    system_failure('empty account name!');

  $values['uid'] = (int) $_SESSION['userinfo']['uid'];

  list($local, $domain) = explode('@', $arr['account'], 2);
  $domain = new Domain( (string) $domain);
  if ($domain->id == NULL)
    $values['domain'] = "NULL";
  else
    $values['domain'] = $domain->id;

  $values['local'] = "'".mysql_real_escape_string($local)."'";

  if (isset($arr['mailbox']))
    if ($arr['mailbox'] == '')
      $values['maildir'] = 'NULL';
    else
      $values['maildir']= "'".mysql_real_escape_string($arr['mailbox'])."'";


  if (isset($arr['password']))
  {
    $values['password'] = "'".encrypt_mail_password($arr['password'])."'";
  }

  if (isset($arr['enabled']))
    $values['aktiv'] = ($arr['enabled'] == 'Y' ? "1" : "0" );


  db_query("INSERT INTO mail.mailaccounts (".implode(',', array_keys($values)).") VALUES (".implode(",", array_values($values)).")");
  logger("modules/imap/include/mailaccounts.php", "imap", "created account »{$arr['account']}«");

}

    
function get_mailaccount_id($accountname)
{
  list($local, $domain) = explode('@', $accountname, 2);

  $local = mysql_real_escape_string($local);
  $domain = mysql_real_escape_string($domain);

  $result = db_query("SELECT acc.id FROM mail.mailaccounts AS acc LEFT JOIN mail.v_domains AS dom ON (dom.id=acc.domain) WHERE local='{$local}' AND dom.domainname='{$domain}'");
  if (mysql_num_rows($result) != 1)
    system_failure('account nicht eindeutig');
  $acc = mysql_fetch_assoc($result);
  return $acc['id'];
}
    

function delete_mailaccount($id)
{
  $id = (int) $id;
  db_query("DELETE FROM mail.mailaccounts WHERE id=".$id." LIMIT 1");
  logger("modules/imap/include/mailaccounts.php", "imap", "deleted account »{$id}«");
}


function check_valid($acc)
{
  $user = $_SESSION['userinfo'];
  DEBUG("Account-data: ".print_r($acc, true));
  DEBUG("User-data: ".print_r($user, true));
  if ($acc['mailbox'] != '')
  {
    if (substr($acc['mailbox'], 0, strlen($user['homedir'])+1) != $user['homedir'].'/')
      return "Die Mailbox muss innerhalb des Home-Verzeichnisses liegen. Sie haben »".$acc['mailbox']."« als Mailbox angegeben, Ihr Home-Verzeichnis ist »".$user['homedir']."/«.";
    if (! check_path($acc['mailbox']))
      return "Sie verwenden ungültige Zeichen in Ihrem Mailbox-Pfad.";
  }

  if ($acc['account'] == '' || strpos($acc['account'], '@') == 0)
    return "Es wurde kein Benutzername angegeben!";
  if (strpos($acc['account'], '@') === false)
    return "Es wurde kein Domain-Teil im Account-Name angegeben. Account-Namen müssen einen Domain-Teil enthalten. Im Zweifel versuchen Sie »@schokokeks.org«.";

  list($local, $domain) = explode('@', $acc['account'], 2);
  verify_input_username($local);
  $tmpdomains = get_domain_list($user['customerno'], $user['uid']);
  $domains = array();
  foreach ($tmpdomains as $dom)
    $domains[] = $dom->fqdn;

  if (array_search($domain, $domains) === false)
  {
    if ($domain == "schokokeks.org")
    {
      if (substr($local, 0, strlen($user['username'])) != $user['username'] || ($acc['account'][strlen($user['username'])] != '-' && $acc['account'][strlen($user['username'])] != '@'))
      {
        return "Sie haben »@schokokeks.org« als Domain-Teil angegeben, aber der Benutzer-Teil beginnt nicht mit Ihrem Benutzername!";
      }
    }
    else
      return "Der angegebene Domain-Teil (»".htmlentities($domain, ENT_QUOTES, "UTF-8")."«) ist nicht für Ihren Account eingetragen. Sollte dies ein Fehler sein, wenden sie sich bitte an einen Administrator!";
  }

  return '';
}



?>

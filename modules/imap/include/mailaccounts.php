<?php

require_once('inc/debug.php');
require_once('inc/db_connect.php');
require_once('inc/base.php');

function mailaccounts($uid)
{
  $uid = (int) $uid;
  $query = "SELECT m.id,concat_ws('@',`m`.`local`,if(isnull(`m`.`domain`),_utf8'schokokeks.org',`d`.`domainname`)) AS `account`, `m`.`password` AS `cryptpass`,`m`.`maildir` AS `maildir`,aktiv from (`mail`.`mailaccounts` `m` left join `mail`.`v_domains` `d` on((`d`.`id` = `m`.`domain`))) WHERE m.uid=$uid";
  DEBUG("SQL-Query: {$query}");
  $result = @mysql_query($query);
  if (mysql_error())
    system_failure(mysql_error());
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
  $query = "SELECT concat_ws('@',`m`.`local`,if(isnull(`m`.`domain`),_utf8'schokokeks.org',`d`.`domainname`)) AS `account`, `m`.`password` AS `cryptpass`,`m`.`maildir` AS `maildir`,aktiv from (`mail`.`mailaccounts` `m` left join `mail`.`v_domains` `d` on((`d`.`id` = `m`.`domain`))) WHERE m.id=$id";
  $result = mysql_query($query);
  DEBUG("Found ".mysql_num_rows($result)." rows!");
  $acc = mysql_fetch_object($result);
  $ret = array('account' => $acc->account, 'mailbox' => $acc->maildir,  'enabled' => ($acc->aktiv == 1));
  DEBUG(print_r($ret, true));
  return $ret;
}

function encrypt_mail_password($pw)
{
  DEBUG("unencrypted PW: ".$pw);
  require_once('inc/base.php');
  $salt = random_string(8);
  $encpw = crypt($pw, "\$1\${$salt}\$");
  DEBUG("encrypted PW: ".$encpw);
  return chop($encpw);

}

function get_domain_id($domain) 
{
  $domain = mysql_real_escape_string($domain);
  $result = mysql_query("SELECT id FROM mail.v_domains WHERE domainname = '{$domain}';");
  if (mysql_num_rows($result) == 0)
    return NULL;
  return mysql_fetch_object($result)->id;
}


function change_mailaccount($id, $arr)
{
  $id = (int) $id;
  $conditions = array();

  if (isset($arr['account']))
  {
    list($local, $domain) = explode('@', $arr['account'], 2);
    $domainid = get_domain_id($domain);
    if ($domainid == NULL)
      $domainid='NULL';
    array_push($conditions, "local='".mysql_real_escape_string($local)."', domain=$domainid");
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


  $query = "UPDATE mail.mailaccounts SET ".implode(",", $conditions)." WHERE id='$id' LIMIT 1";
  DEBUG("Query: ".$query);

  mysql_query($query);
  if (mysql_error())
    system_failure('Beim &Auml;ndern der Account-Daten ist ein Fehler aufgetreten. Sollte dies wiederholt vorkommen, senden Sie bitte die Fehlermeldung ('.mysql_error().') an einen Administrator.');
  logger("modules/imap/include/mailaccounts.php", "imap", "updated account »{$arr['account']}«");

}

function create_mailaccount($arr)
{
  $values = array();

  if (($arr['account']) == '')
    system_failure('empty account name!');

  $values['uid'] = (int) $_SESSION['userinfo']['uid'];

  list($local, $domain) = explode('@', $arr['account'], 2);
  $domainid = get_domain_id($domain);
  if ($domainid == NULL)
    $domainid='NULL';
  $values['local'] = "'".mysql_real_escape_string($local)."'";
  $values['domain'] = $domainid;

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


  $query = "INSERT INTO mail.mailaccounts (".implode(',', array_keys($values)).") VALUES (".implode(",", array_values($values)).")";
  DEBUG("Query: ".$query);

  mysql_query($query);
  if (mysql_error())
    system_failure('Beim Anlegen des Kontos ist ein Fehler aufgetreten. Sollte dies wiederholt vorkommen, senden Sie bitte die Fehlermeldung ('.mysql_error().') an einen Administrator.');
  logger("modules/imap/include/mailaccounts.php", "imap", "created account »{$arr['account']}«");

}


function delete_mailaccount($id)
{
  $id = (int) $id;
  $query = "DELETE FROM mail.mailaccounts WHERE id=".$id." LIMIT 1";
  mysql_query($query);
  if (mysql_error())
    system_failure('Beim L&ouml;schen des Kontos ist ein Fehler aufgetreten. Sollte dies wiederholt vorkommen, senden Sie bitte die Fehlermeldung ('.mysql_error().') an einen Administrator.');
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
      return "Die Mailbox muss innerhalb des Home-Verzeichnisses liegen. Sie haben \"".$acc['mailbox']."\" als Mailbox angegeben, Ihre Home-Verzeichnis ist \"".$user['homedir']."/\".";
    if (strstr($acc['mailbox'], '..') or ! preg_match('/^[a-z0-9.\/_-]*$/', $acc['mailbox']))
      return "Sie verwenden ungültige Zeichen in Ihrem Mailbox-Pfad.";
  }

  if ($acc['account'] == '' || strpos($acc['account'], '@') == 0)
    return "Es wurde kein Benutzername angegeben!";
  if (strpos($acc['account'], '@') === false)
    return "Es wurde kein Domain-Teil im Account-Name angegeben. Account-Namen m&uuml;ssen einen Domain-Teil enthalten. Im Zweifel versuchen Sie &quot;@schokokeks.org&quot;.";

  list($local, $domain) = explode('@', $acc['account'], 2);
  require_once('domains.php');
  $tmpdomains = get_domain_names($user['customerno'], $user['uid']);
  $domains = array();
  foreach ($tmpdomains as $dom)
    array_push($domains, $dom['domainname']);

  if (array_search($domain, $domains) === false)
  {
    if ($domain == "schokokeks.org")
    {
      if (substr($local, 0, strlen($user['username'])) != $user['username'] || ($acc['account'][strlen($user['username'])] != '-' && $acc['account'][strlen($user['username'])] != '@'))
      {
        return "Sie haben &quot;@schokokeks.org&quot; als Domain-Teil angegeben, aber der Benutzer-Teil beginnt nicht mit Ihrem Benutzername!";
      }
    }
    else
      return "Der angegebene Domain-Teil (".htmlentities($domain, ENT_QUOTES, "UTF-8").") ist nicht f&uuml;r Ihren Account eingetragen. Sollte dies ein Fehler sein, wenden sie sich bitte an einen Administrator!";
  }

  return '';
}



?>

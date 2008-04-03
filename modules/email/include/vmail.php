<?php
require_once('inc/base.php');
require_once('inc/debug.php');

require_once('hasdomain.php');

require_once('common.php');

function empty_account()
{
	$account = array(
		'id' => NULL,
		'local' => '',
		'domain' => NULL,
		'password' => NULL,
		'spamfilter' => 'folder',
		'spamexpire' => 7,
		'forwards' => array()
		);
	return $account;

}

function get_account_details($id, $checkuid = true)
{
	$id = (int) $id;
	$uid = (int) $_SESSION['userinfo']['uid'];
	$uid_check = ($checkuid ? "useraccount='{$uid}' AND " : "");
	$result = db_query("SELECT id, local, domain, password, spamfilter, forwards from mail.v_vmail_accounts WHERE {$uid_check}id={$id} LIMIT 1");
	if (mysql_num_rows($result) == 0)
		system_failure('Ungültige ID oder kein eigener Account');
	$acc = empty_account();
	$res = mysql_fetch_assoc($result);
	foreach ($res AS $key => $value) {
	  if ($key == 'forwards')
	    continue;
	  $acc[$key] = $value;
	}
	if ($acc['forwards'] > 0) {
	  $result = db_query("SELECT id, spamfilter, destination FROM mail.vmail_forward WHERE account={$acc['id']};");
	  while ($item = mysql_fetch_assoc($result)){
	    array_push($acc['forwards'], array("id" => $item['id'], 'spamfilter' => $item['spamfilter'], 'destination' => $item['destination']));
	  }
	}
	return $acc;
}

function get_vmail_accounts()
{
	$uid = (int) $_SESSION['userinfo']['uid'];
	$result = db_query("SELECT * from mail.v_vmail_accounts WHERE useraccount='{$uid}'");
	$ret = array();
	while ($line = mysql_fetch_assoc($result))
	{
		array_push($ret, $line);
	}
	DEBUG($ret);
	return $ret;
}



function get_vmail_domains()
{
	$uid = (int) $_SESSION['userinfo']['uid'];
	$result = db_query("SELECT id, domainname FROM mail.v_vmail_domains WHERE useraccount='{$uid}'");
	if (mysql_num_rows($result) == 0)
		system_failure('Sie haben keine Domains für virtuelle Mail-Verarbeitung');
	$ret = array();
	while ($tmp = mysql_fetch_object($result))
		array_push($ret, $tmp);
	return $ret;
}


function find_account_id($accname)
{
  $accname = mysql_real_escape_string($accname);
  DEBUG($accname);
  $tmp = explode('@', $accname, 2);
  DEBUG($tmp);
  if (count($tmp) != 2)
    system_failure("Der Account hat nicht die korrekte Syntax");
  list( $local, $domainname) = $tmp;

  $result = db_query("SELECT id FROM mail.v_vmail_accounts WHERE local='{$local}' AND domainname='{$domainname}' LIMIT 1");
  if (mysql_num_rows($result) == 0)
    system_failure("Der Account konnte nicht gefunden werden");
  $tmp = mysql_fetch_array($result);
  return $tmp[0];
}


function change_vmail_password($accname, $newpass)
{
  $accid = find_account_id($accname);
  $encpw = mysql_real_escape_string(encrypt_mail_password($newpass));
  db_query("UPDATE mail.vmail_accounts SET password='{$encpw}' WHERE id={$accid} LIMIT 1;");
}


function domainselect($selected = NULL, $selectattribute = '')
{
  $domainlist = get_vmail_domains();
  $selected = (int) $selected;

  $ret = '<select id="domain" name="domain" size="1" '.$selectattribute.' >';
  foreach ($domainlist as $dom)
  {
    $s = ($selected == $dom->id) ? ' selected="selected" ': '';
    $ret .= "<option value=\"{$dom->id}\"{$s}>{$dom->domainname}</option>\n";
  }
  $ret .= '</select>';
  return $ret;
}




function save_vmail_account($account)
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $id = $account['id'];
  if ($id != NULL)
  {
    $id = (int) $id;
    $oldaccount = get_account_details($id);
    // Erzeugt einen system_error() wenn ID ungültig
  }
  // Ab hier ist $id sicher, entweder NULL oder eine gültige ID des aktuellen users

  $account['local'] = filter_input_username($account['local']);
  if ($account['local'] == '')
  {
    input_error('Die E-Mail-Adresse braucht eine Angabe vor dem »@«!');
    return false;
  }
  $account['domain'] = (int) $account['domain'];
  $domainlist = get_vmail_domains();
  $valid_domain = false;
  $domainname = NULL;
  foreach ($domainlist as $dom)
  {
    if ($dom->id == $account['domain'])
    {
      $domainname = $dom->domainname;
      $valid_domain = true;
      break;
    }
  }
  if (($account['domain'] == 0) || (! $valid_domain))
  {
    input_error('Bitte wählen Sie eine Ihrer Domains aus!');
    return false;
  }
  
  $forwards = array();
  if (count($account['forwards']) > 0) 
  {
    for ($i=0;$i < count($account['forwards']); $i++)
    {
      if ($account['forwards'][$i]['spamfilter'] != 'tag' && $account['forwards'][$i]['spamfilter'] != 'delete')
        $account['forwards'][$i]['spamfilter'] = '';
      $account['forwards'][$i]['destination'] = filter_input_general($account['forwards'][$i]['destination']);
      if (! check_emailaddr($account['forwards'][$i]['destination']))
        system_failure('Das Weiterleitungs-Ziel »'.$account['forwards'][$i]['destination'].'« ist keine E-Mail-Adresse!');
    }
  }
    
  $password='NULL';
  if ($account['password'] != '')
  {
    $account['password'] = stripslashes($account['password']);
    $crack = strong_password($account['password']);
    if ($crack !== true)
    {
      input_error('Ihr Passwort ist zu einfach. bitte wählen Sie ein sicheres Passwort!'."\nDie Fehlermeldung lautet: »{$crack}«");
      return false;
    }
    $password = "'".encrypt_mail_password($account['password'])."'";
  }
  $set_password = ($id == NULL || $password != 'NULL');
  if ($account['password'] === NULL)
    $set_password=true;

  $spam = 'NULL';
  switch ($account['spamfilter'])
  {
    case 'folder':
      $spam = "'folder'";
      break;
    case 'tag':
      $spam = "'tag'";
      break;
    case 'delete':
      $spam = "'delete'";
      break;
  }

  $account['local'] = mysql_real_escape_string($account['local']);
  $account['password'] = mysql_real_escape_string($account['password']);
  $account['spamexpire'] = (int) $account['spamexpire'];

  $query = '';
  if ($id == NULL)
  {
    $query = "INSERT INTO mail.vmail_accounts (local, domain, spamfilter, spamexpire, password) VALUES ";
    $query .= "('{$account['local']}', {$account['domain']}, {$spam}, {$account['spamexpire']}, {$password});";
  }
  else
  {
    if ($set_password)
      $password=", password={$password}";
    else
      $password='';
    $query = "UPDATE mail.vmail_accounts SET local='{$account['local']}', domain={$account['domain']}{$password}, ";
    $query .= "spamfilter={$spam}, spamexpire={$account['spamexpire']} ";
    $query .= "WHERE id={$id} LIMIT 1;";
  }
  db_query($query); 
  if ($id)
    db_query("DELETE FROM mail.vmail_forward WHERE account={$id}");
  else
    $id = mysql_insert_id();

  if (count($account['forwards']) > 0)
  {
    $forward_query = "INSERT INTO mail.vmail_forward (account,spamfilter,destination) VALUES ";
    $first = true;
    for ($i=0;$i < count($account['forwards']); $i++)
    { 
      if ($first)
        $first = false;
      else
        $forward_query .= ', ';
      $forward_query .= "({$id}, ".maybe_null($account['forwards'][$i]['spamfilter']).", '{$account['forwards'][$i]['destination']}')";
    }
    db_query($forward_query);
  }
  if ($account['password'] != 'NULL')
  {
    # notify the vmail subsystem of this new account
    mail('vmail@schokokeks.org', 'command', "user={$account['local']}\nhost={$domainname}", "X-schokokeks-org-message: command");
  }
}



function delete_account($id)
{
  $account = get_account_details($id);
  db_query("DELETE FROM mail.vmail_accounts WHERE id={$account['id']};");
}



function domainsettings($only_domain=NULL) {
  $uid = (int) $_SESSION['userinfo']['uid'];
  if ($domain)
    $only_domain = (int) $only_domain;
  $result = db_query("SELECT d.id, CONCAT_WS('.',d.domainname,d.tld) AS name, d.mail, m.id AS m_id, v.id AS v_id, IF(ISNULL(v.hostname),m.subdomain,v.hostname) AS hostname FROM kundendaten.domains AS d LEFT JOIN mail.virtual_mail_domains AS v ON (d.id=v.domain) LEFT JOIN mail.custom_mappings AS m ON (d.id=m.domain) WHERE d.useraccount={$uid} OR m.uid={$uid};");
  $domains = array();
  $subdomains = array();
  while ($mydom = mysql_fetch_assoc($result)) {
    if (! array_key_exists($mydom['id'], $domains)) {
      if ($mydom['v_id'] && ! $mydom['hostname'])
        $mydom['mail'] = 'virtual';
      $domains[$mydom['id']] = array(
        "name" => $mydom['name'],
        "type" => $mydom['mail']
        );
      if ($only_domain && $only_domain == $mydom['id'])
        return $domains[$only_domain];
    }
    if ($mydom['hostname']) {
      if (! array_key_exists($mydom['id'], $subdomains))
        $subdomains[$mydom['id']] = array();
        
      $type = 'auto';
      if ($mydom['v_id'])
        $type = 'virtual';
      $subdomains[$mydom['id']][] = array(
        "name" => $mydom['hostname'],
        "type" => $type
        );
    }
  }
  return array("domains" => $domains, "subdomains" => $subdomains);
}


function domain_has_vmail_accounts($domid)
{
  $domid = (int) $domid;
  $result = db_query("SELECT dom.id FROM mail.vmail_accounts AS acc LEFT JOIN mail.virtual_mail_domains AS dom ON (dom.id=acc.domain) WHERE dom.domain={$domid}");
  return (mysql_num_rows($result) > 0);
}


function change_domain($id, $type)
{
  $id = (int) $id;
  $type = mysql_real_escape_string($type);
  if (domain_has_vmail_accounts($id))
    system_failure("Sie müssen zuerst alle E-Mail-Konten mit dieser Domain löschen, bevor Sie die Webinterface-Verwaltung für diese Domain abschalten können.");
  
  if (! in_array($type, array('none','auto','virtual')))
    system_failure("Ungültige Aktion");
  
  $old = domainsettings($id);
  if ($old['type'] == $type)
    system_failure('Domain ist bereits so konfiguriert');

  if ($type == 'none') {
    db_query("DELETE FROM mail.virtual_mail_domains WHERE domain={$id} AND hostname IS NULL LIMIT 1;");
    db_query("DELETE FROM mail.custom_mappings WHERE domain={$id} AND subdomain IS NULL LIMIT 1;");
    db_query("UPDATE kundendaten.domains SET mail='none' WHERE id={$id} LIMIT 1;");
  }
  elseif ($type == 'virtual') {
    db_query("DELETE FROM mail.custom_mappings WHERE domain={$id} AND subdomain IS NULL LIMIT 1;");
    db_query("UPDATE kundendaten.domains SET mail='auto' WHERE id={$id} LIMIT 1;");    db_query("INSERT INTO mail.virtual_mail_domains (domain) VALUES ({$id});");
  }
  elseif ($type == 'auto') {
    db_query("DELETE FROM mail.virtual_mail_domains WHERE domain={$id} AND hostname IS NULL LIMIT 1;");
    db_query("DELETE FROM mail.custom_mappings WHERE domain={$id} AND subdomain IS NULL LIMIT 1;");
    db_query("UPDATE kundendaten.domains SET mail='auto' WHERE id={$id} LIMIT 1;");
  }
}


/*
function maildomain_type($type) {
  switch ($type) {
    case 'none':
      $type = 'Diese Domain empfängt keine E-Mails';
      break;
    case 'auto':
      $type = 'E-Mail-Adressen werden manuell über .courier-Dateien verwaltet';
      break;
    case 'virtual':
      $type = 'E-Mail-Adressen werden über Webinterface verwaltet';
      break;
    case 'manual':
      $type = 'Manuelle Konfiguration, kann nur von den Admins geändert werden';
      break;
  }
  return $type;
}
*/

function maildomain_type($type) {
  switch ($type) {
    case 'none':
      $type = 'Deaktiviert';
      break;
    case 'auto':
      $type = '.courier-Dateien';
      break;
    case 'virtual':
      $type = 'Webinterface';
      break;
    case 'manual':
      $type = 'Manuell';
      break;
  }
  return $type;
}



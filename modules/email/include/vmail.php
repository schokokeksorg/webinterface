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
    'quota' => config('vmail_basequota'),
    'quota_threshold' => 20,
		'forwards' => array()
		);
	return $account;

}

function get_account_details($id, $checkuid = true)
{
	$id = (int) $id;
	$uid = (int) $_SESSION['userinfo']['uid'];
	$uid_check = ($checkuid ? "useraccount='{$uid}' AND " : "");
	$result = db_query("SELECT id, local, domain, password, spamfilter, forwards, server, quota, quota_used, quota_threshold from mail.v_vmail_accounts WHERE {$uid_check}id={$id} LIMIT 1");
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
  if ($acc['quota_threshold'] === NULL) {
    $acc['quota_threshold'] = -1;
  }
	return $acc;
}

function get_vmail_accounts()
{
	$uid = (int) $_SESSION['userinfo']['uid'];
	$result = db_query("SELECT * from mail.v_vmail_accounts WHERE useraccount='{$uid}' ORDER BY domainname,local ASC");
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
	$result = db_query("SELECT id, domainname, server FROM mail.v_vmail_domains WHERE useraccount='{$uid}'");
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


function get_max_mailboxquota($server, $oldquota) {
  $uid = (int) $_SESSION['userinfo']['uid'];
  $server = (int) $server;
  $result = db_query("SELECT systemquota - (systemquota_used + mailquota) AS free FROM system.v_quota WHERE uid='{$uid}' AND server='{$server}'");
  $item = mysql_fetch_assoc($result);
  DEBUG("Free space: ".$item['free']." / Really: ".($item['free'] + ($oldquota - config('vmail_basequota'))));
  return $item['free'] + ($oldquota - config('vmail_basequota'));
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
  
  $newaccount = false;
  if ($id === NULL) {
    $newaccount = true;
  }
  $account['local'] = filter_input_username($account['local']);
  if ($account['local'] == '')
  {
    system_failure('Die E-Mail-Adresse braucht eine Angabe vor dem »@«!');
    return false;
  }
  $account['domain'] = (int) $account['domain'];
  $domainlist = get_vmail_domains();
  $valid_domain = false;
  $domainname = NULL;
  $server = NULL;
  foreach ($domainlist as $dom)
  {
    if ($dom->id == $account['domain'])
    {
      $domainname = $dom->domainname;
      $server = $dom->server;
      $valid_domain = true;
      break;
    }
  }
  if (($account['domain'] == 0) || (! $valid_domain))
  {
    system_failure('Bitte wählen Sie eine Ihrer Domains aus!');
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
      system_failure('Ihr Passwort ist zu einfach. bitte wählen Sie ein sicheres Passwort!'."\nDie Fehlermeldung lautet: »{$crack}«");
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
  
  $free = config('vmail_basequota');
  if ($newaccount) {
    // Neues Postfach
    $free = get_max_mailboxquota($server, config('vmail_basequota'));
  } else {
    $free = get_max_mailboxquota($oldaccount['server'], $oldaccount['quota']);
  }
  
  $newquota = max((int) config('vmail_basequota'), (int) $account['quota']);
  if ($newquota > config('vmail_basequota') && $newquota > ($free+config('vmail_basequota'))) {
    $newquota = $free + config('vmail_basequota');
    warning("Ihr Speicherplatz reicht für diese Postfach-Größe nicht mehr aus. Ihr Postfach wurde auf {$newquota} MB reduziert. Bitte beachten Sie, dass damit Ihr Benutzerkonto keinen freien Speicherplatz mehr aufweist!");
  }
  
  $account['quota'] = $newquota;

  if ($account['quota_threshold'] == -1) {
    $account['quota_threshold'] = 'NULL';
  }
  else {
    $account['quota_threshold'] = min( (int) $account['quota_threshold'], (int) $account['quota'] );
  }
  
  $account['local'] = mysql_real_escape_string($account['local']);
  $account['password'] = mysql_real_escape_string($account['password']);
  $account['spamexpire'] = (int) $account['spamexpire'];

  $query = '';
  if ($newaccount)
  {
    $query = "INSERT INTO mail.vmail_accounts (local, domain, spamfilter, spamexpire, password, quota, quota_threshold) VALUES ";
    $query .= "('{$account['local']}', {$account['domain']}, {$spam}, {$account['spamexpire']}, {$password}, {$account['quota']}, {$account['quota_threshold']});";
    db_query($query); 
    $id = mysql_insert_id();
  }
  else
  {
    if ($set_password)
      $password=", password={$password}";
    else
      $password='';
    $query = "UPDATE mail.vmail_accounts SET local='{$account['local']}', domain={$account['domain']}{$password}, ";
    $query .= "spamfilter={$spam}, spamexpire={$account['spamexpire']}, quota={$account['quota']}, quota_threshold={$account['quota_threshold']} ";
    $query .= "WHERE id={$id} LIMIT 1;";
    db_query($query); 
  }
  if (! $newaccount)
    db_query("DELETE FROM mail.vmail_forward WHERE account={$id}");

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
  if ($newaccount && $password != 'NULL')
  {
    $emailaddr = $account['local'].'@'.$domainname;
    $webmailurl = config('webmail_url');
    $servername = get_server_by_id($server);
    $message = 'Ihr neues E-Mail-Postfach '.$emailaddr.' ist einsatzbereit!

Wenn Sie diese Nachricht sehen, haben Sie das Postfach erfolgreich 
abgerufen. Sie können diese Nachricht nach Kenntnisnahme löschen.

Wussten Sie schon, dass Sie auf mehrere Arten Ihre E-Mails abrufen können?

- Für unterwegs: Webmail
  Rufen Sie dazu einfach die Seite '.$webmailurl.' auf und 
  geben Sie Ihre E-Mail-Adresse und das Passwort ein.

- Mit Ihrem Computer oder Smartphone: IMAP oder POP3
  Tragen Sie bitte folgende Zugangsdaten in Ihrem Programm ein:
    Server-Name: '.$servername.'
    Benutzername: '.$emailaddr.'
  (Achten Sie bitte darauf, dass die Verschlüsselung mit SSL oder TLS 
  aktiviert ist.)
';
    # send welcome message
    mail($emailaddr, 'Ihr neues Postfach ist bereit', $message, "X-schokokeks-org-message: welcome\nFrom: ".config('company_name').' <'.config('adminmail').">\nMIME-Version: 1.0\nContent-Type: text/plain; charset=UTF-8\n");
    # notify the vmail subsystem of this new account
    #mail('vmail@'.config('vmail_server'), 'command', "user={$account['local']}\nhost={$domainname}", "X-schokokeks-org-message: command");
  }

  // Update Mail-Quota-Cache
  $result = db_query("SELECT useraccount, server, SUM(quota-(SELECT value FROM misc.config WHERE `key`='vmail_basequota')) AS quota, SUM(GREATEST(quota_used-(SELECT value FROM misc.config WHERE `key`='vmail_basequota'), 0)) AS used FROM mail.v_vmail_accounts GROUP BY useraccount, server");
  while ($line = mysql_fetch_assoc($result)) {
    if ($line['quota'] !== NULL) {
      db_query("REPLACE INTO mail.vmailquota (uid, server, quota, used) VALUES ('{$line['useraccount']}', '{$line['server']}', '{$line['quota']}', '{$line['used']}')");
    }
  }

  return true;
}



function delete_account($id)
{
  $account = get_account_details($id);
  db_query("DELETE FROM mail.vmail_accounts WHERE id={$account['id']};");
}



function domainsettings($only_domain=NULL) {
  $uid = (int) $_SESSION['userinfo']['uid'];
  if ($only_domain)
    $only_domain = (int) $only_domain;
  $domains = array();
  $subdomains = array();

  // Domains
  $result = db_query("SELECT d.id, CONCAT_WS('.',d.domainname,d.tld) AS name, d.mail, m.id AS m_id, v.id AS v_id FROM kundendaten.domains AS d LEFT JOIN mail.virtual_mail_domains AS v ON (d.id=v.domain AND v.hostname IS NULL) LEFT JOIN mail.custom_mappings AS m ON (d.id=m.domain AND m.subdomain IS NULL) WHERE d.useraccount={$uid} OR m.uid={$uid};");

  while ($mydom = mysql_fetch_assoc($result)) {
    if (! array_key_exists($mydom['id'], $domains)) {
      if ($mydom['v_id'])
        $mydom['mail'] = 'virtual';
      $domains[$mydom['id']] = array(
        "name" => $mydom['name'],
        "type" => $mydom['mail']
        );
      if ($only_domain && $only_domain == $mydom['id'])
        return $domains[$only_domain];
    }
  }      

  // Subdomains
  $result = db_query("SELECT d.id, CONCAT_WS('.',d.domainname,d.tld) AS name, d.mail, m.id AS m_id, v.id AS v_id, IF(ISNULL(v.hostname),m.subdomain,v.hostname) AS hostname FROM kundendaten.domains AS d LEFT JOIN mail.virtual_mail_domains AS v ON (d.id=v.domain AND v.hostname IS NOT NULL) LEFT JOIN mail.custom_mappings AS m ON (d.id=m.domain AND m.subdomain IS NOT NULL) WHERE (m.id IS NOT NULL OR v.id IS NOT NULL) AND d.useraccount={$uid} OR m.uid={$uid};");
  while ($mydom = mysql_fetch_assoc($result)) {
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
    db_query("UPDATE kundendaten.domains SET mail='none', lastchange=NOW() WHERE id={$id} LIMIT 1;");
  }
  elseif ($type == 'virtual') {
    $vmailserver = (int) $_SESSION['userinfo']['server'];
    db_query("DELETE FROM mail.custom_mappings WHERE domain={$id} AND subdomain IS NULL LIMIT 1;");
    db_query("UPDATE kundendaten.domains SET mail='auto', lastchange=NOW() WHERE id={$id} LIMIT 1;");
    db_query("INSERT INTO mail.virtual_mail_domains (domain, server) VALUES ({$id}, {$vmailserver});");
  }
  elseif ($type == 'auto') {
    db_query("DELETE FROM mail.virtual_mail_domains WHERE domain={$id} AND hostname IS NULL LIMIT 1;");
    db_query("DELETE FROM mail.custom_mappings WHERE domain={$id} AND subdomain IS NULL LIMIT 1;");
    db_query("UPDATE kundendaten.domains SET mail='auto', lastchange=NOW() WHERE id={$id} LIMIT 1;");
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



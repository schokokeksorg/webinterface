<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2014 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/base.php');
require_once('inc/debug.php');

require_once('hasdomain.php');

require_once('common.php');


$forced_spamfilter_domains = array(
  't-online.de', 'gmx.de', 'gmx.net', 'web.de', 'gmail.com', 'googlemail.com',
  'gmail.com', 'googlemail.de', 'freenet.de', 'aol.com', 'yahoo.com', 'gmx.at', 
  'ymail.com', 'hotmail.com', 'mail.de', 'email.de', 'online.de', 'outlook.com',
  'me.com'
  );


function forward_type($target) {
  global $forced_spamfilter_domains;
  list($l, $d) = explode('@', strtolower($target), 2);
  DEBUG('Weiterleitung an '.$l.' @ '.$d);
  if (in_array($d, $forced_spamfilter_domains)) {
    // Domain in der Liste => Spam darf nicht weiter geleitet werden
    return 'critical';
  }
  $result = db_query("SELECT id FROM kundendaten.domains WHERE CONCAT_WS('.', domainname, tld) = ?", array($d));
  if ($result->rowCount() > 0) {
    // Lokale Domain
    return 'local';
  }  
  // Auswärtige Domain aber keine aus der Liste
  return 'external';
}



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
		'forwards' => array(),
		'autoresponder' => NULL
		);
	return $account;

}

function empty_autoresponder_config()
{
  $ar = array(
    'valid_from' => date( 'Y-m-d' ),
    'valid_until' => NULL,
    'fromname' => NULL,
    'fromaddr' => NULL,
    'subject' => NULL,
    'message' => 'Danke für Ihre E-Mail.
Ich bin aktuell nicht im Büro und werde Ihre Nachricht erst nach meiner Rückkehr beantworten.
Ihre E-Mail wird nicht weitergeleitet.',
    'quote' => NULL
    );
  return $ar;
}


function get_vmail_id_by_emailaddr($emailaddr) 
{
  $result = db_query("SELECT id FROM mail.v_vmail_accounts WHERE CONCAT(local, '@', domainname) = ?", array($emailaddr));
  $entry = $result->fetch();
  return (int) $entry['id'];
}

function get_account_details($id, $checkuid = true)
{
	$id = (int) $id;
  $uid_check = '';
  DEBUG("checkuid: ".$checkuid);
  $args = array(":id" => $id);
  if ($checkuid) {
    $uid = (int) $_SESSION['userinfo']['uid'];
    $uid_check = "useraccount=:uid AND ";
    $args[":uid"] = $uid;
  }
  $result = db_query("SELECT id, local, domain, password, spamfilter, forwards, autoresponder, server, quota, COALESCE(quota_used, 0) AS quota_used, quota_threshold from mail.v_vmail_accounts WHERE {$uid_check}id=:id LIMIT 1", $args);
	if ($result->rowCount() == 0)
		system_failure('Ungültige ID oder kein eigener Account');
	$acc = empty_account();
	$res = $result->fetch();
	foreach ($res AS $key => $value) {
	  if ($key == 'forwards')
	    continue;
	  $acc[$key] = $value;
	}
	if ($acc['forwards'] > 0) {
	  $result = db_query("SELECT id, spamfilter, destination FROM mail.vmail_forward WHERE account=?", array($acc['id']));
	  while ($item = $result->fetch()){
	    array_push($acc['forwards'], array("id" => $item['id'], 'spamfilter' => $item['spamfilter'], 'destination' => $item['destination']));
	  }
	}
  if ($acc['autoresponder'] > 0) {
    $result = db_query("SELECT id, IF(valid_from IS NULL OR valid_from > NOW() OR valid_until < NOW(), 0, 1) AS active, DATE(valid_from) AS valid_from, DATE(valid_until) AS valid_until, fromname, fromaddr, subject, message, quote FROM mail.vmail_autoresponder WHERE account=?", array($acc['id']));
    $item = $result->fetch();
    DEBUG($item);
    $acc['autoresponder'] = $item;
  } else {
    $acc['autoresponder'] = NULL;
  }
  if ($acc['quota_threshold'] === NULL) {
    $acc['quota_threshold'] = -1;
  }
	return $acc;
}

function get_vmail_accounts()
{
	$uid = (int) $_SESSION['userinfo']['uid'];
	$result = db_query("SELECT * from mail.v_vmail_accounts WHERE useraccount=? ORDER BY domainname,local ASC", array($uid));
	$ret = array();
	while ($line = $result->fetch())
	{
		array_push($ret, $line);
	}
	DEBUG($ret);
	return $ret;
}



function get_vmail_domains()
{
	$uid = (int) $_SESSION['userinfo']['uid'];
	$result = db_query("SELECT id, domainname, server FROM mail.v_vmail_domains WHERE useraccount=? ORDER BY domainname", array($uid));
	if ($result->rowCount() == 0)
		system_failure('Sie haben keine Domains für virtuelle Mail-Verarbeitung');
	$ret = array();
	while ($tmp = $result->fetch())
		array_push($ret, $tmp);
	return $ret;
}


function find_account_id($accname)
{
  DEBUG($accname);
  $tmp = explode('@', $accname, 2);
  DEBUG($tmp);
  if (count($tmp) != 2)
    system_failure("Der Account hat nicht die korrekte Syntax");
  list( $local, $domainname) = $tmp;

  $result = db_query("SELECT id FROM mail.v_vmail_accounts WHERE local=? AND domainname=? LIMIT 1", array($local, $domainname));
  if ($result->rowCount() == 0)
    system_failure("Der Account konnte nicht gefunden werden");
  $tmp = $result->fetch();
  return $tmp[0];
}


function change_vmail_password($accname, $newpass)
{
  $accid = find_account_id($accname);
  $encpw = encrypt_mail_password($newpass);
  db_query("UPDATE mail.vmail_accounts SET password=:encpw WHERE id=:accid", array(":encpw" => $encpw, ":accid" => $accid));
}


function domainselect($selected = NULL, $selectattribute = '')
{
  $domainlist = get_vmail_domains();
  $selected = (int) $selected;

  $ret = '<select id="domain" name="domain" size="1" '.$selectattribute.' >';
  foreach ($domainlist as $dom)
  {
    $s = ($selected == $dom['id']) ? ' selected="selected" ': '';
    $ret .= "<option value=\"{$dom['id']}\"{$s}>{$dom['domainname']}</option>\n";
  }
  $ret .= '</select>';
  return $ret;
}


function get_max_mailboxquota($server, $oldquota) {
  $uid = (int) $_SESSION['userinfo']['uid'];
  $server = (int) $server;
  $result = db_query("SELECT systemquota - (COALESCE(systemquota_used,0) + COALESCE(mailquota,0)) AS free FROM system.v_quota WHERE uid=:uid AND server=:server", array(":uid" => $uid, ":server" => $server));
  $item = $result->fetch();
  DEBUG("Free space: ".$item['free']." / Really: ".($item['free'] + ($oldquota - config('vmail_basequota'))));
  return $item['free'] + ($oldquota - config('vmail_basequota'));
}




function save_vmail_account($account)
{
  $accountlogin = ($_SESSION['role'] == ROLE_VMAIL_ACCOUNT);
  $id = $account['id'];
  if ($id != NULL)
  {
    $id = (int) $id;
    $oldaccount = get_account_details($id, !$accountlogin);
    // Erzeugt einen system_error() wenn ID ungültig
  }
  // Ab hier ist $id sicher, entweder NULL oder eine gültige ID des aktuellen users

  $newaccount = false;
  if ($id === NULL) {
    $newaccount = true;
  }

  if ($accountlogin) {
    if ($account['domain'] != $oldaccount['domain'])
      system_failure('Sie können die E-Mail-Adresse nicht ändern!');
    if ($account['local'] != $oldaccount['local'])
      system_failure('Sie können die E-Mail-Adresse nicht ändern!');
    if ($account['quota'] != $oldaccount['quota'])
      system_failure('Sie können Ihren eigenen Speicherplatz nicht verändern.');
  } else {
  
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
      if ($dom['id'] == $account['domain'])
      {
        $domainname = $dom['domainname'];
        $server = $dom['server'];
        $valid_domain = true;
        break;
      }
    }
    if (($account['domain'] == 0) || (! $valid_domain))
    {
      system_failure('Bitte wählen Sie eine Ihrer Domains aus!');
      return false;
    }
  }
  
  $forwards = array();
  if (count($account['forwards']) > 0) 
  {
    for ($i=0;$i < count($account['forwards']); $i++)
    {
      if ($account['forwards'][$i]['spamfilter'] != 'tag' && $account['forwards'][$i]['spamfilter'] != 'delete') {
        $account['forwards'][$i]['spamfilter'] = NULL;
      }
      $account['forwards'][$i]['destination'] = filter_input_general($account['forwards'][$i]['destination']);
      if (! check_emailaddr($account['forwards'][$i]['destination'])) {
        system_failure('Das Weiterleitungs-Ziel »'.$account['forwards'][$i]['destination'].'« ist keine E-Mail-Adresse!');
      }
    }
  }

  if ($accountlogin) {
    $password = NULL; 
    $set_password = false;
  } else {
    $password= NULL;
    if ($account['password'] != '')
    {
      $account['password'] = stripslashes($account['password']);
      $crack = strong_password($account['password']);
      if ($crack !== true)
      {
        system_failure('Ihr Passwort ist zu einfach. bitte wählen Sie ein sicheres Passwort!'."\nDie Fehlermeldung lautet: »{$crack}«");
        return false;
      }
      $password = encrypt_mail_password($account['password']);
    }
    $set_password = ($id == NULL || $password != NULL);
    if ($account['password'] === NULL) {
      $set_password=true;
    }
  }  

  $spam = NULL;
  switch ($account['spamfilter'])
  {
    case 'folder':
      $spam = "folder";
      break;
    case 'tag':
      $spam = "tag";
      break;
    case 'delete':
      $spam = "delete";
      break;
  }
  
  if (!$accountlogin) {
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
  }  

  if ($account['quota_threshold'] == -1) {
    $account['quota_threshold'] = NULL;
  }
  else {
    $account['quota_threshold'] = min( (int) $account['quota_threshold'], (int) $account['quota'] );
  }
  
  $account['local'] = strtolower($account['local']);
  $account['spamexpire'] = (int) $account['spamexpire'];

  $args = array(":local" => $account['local'],
                ":domain" => $account['domain'],
                ":password" => $password,
                ":spamfilter" => $spam,
                ":spamexpire" => $account['spamexpire'],
                ":quota" => $account['quota'], 
                ":quota_threshold" => $account['quota_threshold'],
                ":id" => $id
                );
  $query = '';
  if ($newaccount)
  {
    unset($args[":id"]);
    $query = "INSERT INTO mail.vmail_accounts (local, domain, spamfilter, spamexpire, password, quota, quota_threshold) VALUES (:local, :domain, :spamfilter, :spamexpire, :password, :quota, :quota_threshold)";
  } else {
    if ($set_password)
      $pw=", password=:password";
    else {
      unset($args[":password"]);
      $pw='';
    }
    $query = "UPDATE mail.vmail_accounts SET local=:local, domain=:domain{$pw}, spamfilter=:spamfilter, spamexpire=:spamexpire, quota=:quota, quota_threshold=:quota_threshold WHERE id=:id";
  }
  db_query($query, $args); 
  if ($newaccount) {
    $id = db_insert_id();
  }

  if (is_array($account['autoresponder'])) {
    $ar = $account['autoresponder'];
    $quote = "inline";
    if ($ar['quote'] == 'attach')
      $quote = "attach";
    elseif ($ar['quote'] == NULL)
      $quote = NULL;
    $query = "REPLACE INTO mail.vmail_autoresponder (account, valid_from, valid_until, fromname, fromaddr, subject, message, quote) ".
             "VALUES (:id, :valid_from, :valid_until, :fromname, :fromaddr, :subject, :message, :quote)";
    $args = array(":id" => $id,
                  ":valid_from" => $ar['valid_from'],
                  ":valid_until" => $ar['valid_until'],
                  ":fromname" => $ar['fromname'],
                  ":fromaddr" => check_emailaddr($ar['fromaddr']),
                  ":subject" => $ar['subject'],
                  ":message" => $ar['message'],
                  ":quote" => $quote);
    db_query($query, $args);
  }
    


  if (! $newaccount)
    db_query("DELETE FROM mail.vmail_forward WHERE account=?", array($id));

  if (count($account['forwards']) > 0)
  {
    $forward_query = "INSERT INTO mail.vmail_forward (account,spamfilter,destination) VALUES (:account, :spamfilter, :destination)";
    for ($i=0;$i < count($account['forwards']); $i++)
    { 
      db_query($forward_query, array(":account" => $id, ":spamfilter" => $account['forwards'][$i]['spamfilter'], ":destination" => $account['forwards'][$i]['destination']));
    }
  }
  if ($newaccount && $password)
  {
    $servername = get_server_by_id($server);
    $emailaddr = 'vmail-'.$account['local'].'%'.$domainname.'@'.$servername;
    $username = $account['local'].'@'.$domainname;
    $webmailurl = config('webmail_url');
    $servername = get_server_by_id($server);
    $message = 'Ihr neues E-Mail-Postfach '.$username.' ist einsatzbereit!

Wenn Sie diese Nachricht sehen, haben Sie das Postfach erfolgreich 
abgerufen. Sie können diese Nachricht nach Kenntnisnahme löschen.

Wussten Sie schon, dass Sie auf mehrere Arten Ihre E-Mails abrufen können?

- Für unterwegs: Webmail
  Rufen Sie dazu einfach die Seite '.$webmailurl.' auf und 
  geben Sie Ihre E-Mail-Adresse und das Passwort ein.

- Mit Ihrem Computer oder Smartphone: IMAP oder POP3
  Tragen Sie bitte folgende Zugangsdaten in Ihrem Programm ein:
    Server-Name: '.$servername.'
    Benutzername: '.$username.'
  (Achten Sie bitte darauf, dass die Verschlüsselung mit SSL oder TLS 
  aktiviert ist.)
';
    # send welcome message
    mail($emailaddr, 'Ihr neues Postfach ist bereit', $message, "X-schokokeks-org-message: welcome\nFrom: ".config('company_name').' <'.config('adminmail').">\nMIME-Version: 1.0\nContent-Type: text/plain; charset=UTF-8\n");
    # notify the vmail subsystem of this new account
    #mail('vmail@'.config('vmail_server'), 'command', "user={$account['local']}\nhost={$domainname}", "X-schokokeks-org-message: command");
  }

  // Clean up obsolete quota
  if ($_SESSION['role'] == ROLE_SYSTEMUSER) {
    db_query("UPDATE mail.vmail_accounts SET quota_used=NULL, quota=NULL WHERE password IS NULL");
  }

  // Update Mail-Quota-Cache
  if ($_SESSION['role'] == ROLE_SYSTEMUSER) {
    $uid = (int) $_SESSION['userinfo']['uid'];
    $result = db_query("SELECT useraccount, server, SUM(quota-(SELECT value FROM misc.config WHERE `key`='vmail_basequota')) AS quota, SUM(GREATEST(quota_used-(SELECT value FROM misc.config WHERE `key`='vmail_basequota'), 0)) AS used FROM mail.v_vmail_accounts WHERE useraccount=? GROUP BY useraccount, server", array($uid));
    while ($line = $result->fetch()) {
      if ($line['quota'] !== NULL) {
        db_query("REPLACE INTO mail.vmailquota (uid, server, quota, used) VALUES (:uid, :server, :quota, :used)", array(":uid" => $line['useraccount'], ":server" => $line['server'], ":quota" => $line['quota'], ":used" => $line['used']));
      }
    }
  }

  return true;
}



function delete_account($id)
{
  $account = get_account_details($id);
  db_query("DELETE FROM mail.vmail_accounts WHERE id=?", array($account['id']));
}



function domainsettings($only_domain=NULL) {
  $uid = (int) $_SESSION['userinfo']['uid'];
  if ($only_domain)
    $only_domain = (int) $only_domain;
  $domains = array();
  $subdomains = array();

  // Domains
  $result = db_query("SELECT d.id, CONCAT_WS('.',d.domainname,d.tld) AS name, d.mail, d.mailserver_lock, m.id AS m_id, v.id AS v_id FROM kundendaten.domains AS d LEFT JOIN mail.virtual_mail_domains AS v ON (d.id=v.domain AND v.hostname IS NULL) LEFT JOIN mail.custom_mappings AS m ON (d.id=m.domain AND m.subdomain IS NULL) WHERE d.useraccount=:uid OR m.uid=:uid ORDER BY CONCAT_WS('.',d.domainname,d.tld);", array(":uid" => $uid));

  while ($mydom = $result->fetch()) {
    if (! array_key_exists($mydom['id'], $domains)) {
      if ($mydom['v_id'])
        $mydom['mail'] = 'virtual';
      $domains[$mydom['id']] = array(
        "name" => $mydom['name'],
        "type" => $mydom['mail'],
        "mailserver_lock" => $mydom['mailserver_lock']
        );
      if ($only_domain && $only_domain == $mydom['id'])
        return $domains[$only_domain];
    }
  }      

  // Subdomains
  $result = db_query("SELECT d.id, CONCAT_WS('.',d.domainname,d.tld) AS name, d.mail, m.id AS m_id, v.id AS v_id, IF(ISNULL(v.hostname),m.subdomain,v.hostname) AS hostname FROM kundendaten.domains AS d LEFT JOIN mail.virtual_mail_domains AS v ON (d.id=v.domain AND v.hostname IS NOT NULL) LEFT JOIN mail.custom_mappings AS m ON (d.id=m.domain AND m.subdomain IS NOT NULL) WHERE (m.id IS NOT NULL OR v.id IS NOT NULL) AND d.useraccount=:uid OR m.uid=:uid;", array(":uid" => $uid));
  while ($mydom = $result->fetch()) {
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
  $result = db_query("SELECT dom.id FROM mail.vmail_accounts AS acc LEFT JOIN mail.virtual_mail_domains AS dom ON (dom.id=acc.domain) WHERE dom.domain=?", array($domid));
  return ($result->rowCount() > 0);
}


function change_domain($id, $type)
{
  $id = (int) $id;
  if (domain_has_vmail_accounts($id))
    system_failure("Sie müssen zuerst alle E-Mail-Konten mit dieser Domain löschen, bevor Sie die Webinterface-Verwaltung für diese Domain abschalten können.");
  
  if (! in_array($type, array('none','auto','virtual')))
    system_failure("Ungültige Aktion");
  
  $old = domainsettings($id);
  if ($old['type'] == $type)
    system_failure('Domain ist bereits so konfiguriert');

  if ($type == 'none') {
    db_query("DELETE FROM mail.virtual_mail_domains WHERE domain=? AND hostname IS NULL", array($id));
    db_query("DELETE FROM mail.custom_mappings WHERE domain=? AND subdomain IS NULL", array($id));
    db_query("UPDATE kundendaten.domains SET mail='none', lastchange=NOW() WHERE id=?", array($id));
  }
  elseif ($type == 'virtual') {
    $vmailserver = (int) $_SESSION['userinfo']['server'];
    db_query("DELETE FROM mail.custom_mappings WHERE domain=? AND subdomain IS NULL", array($id));
    db_query("UPDATE kundendaten.domains SET mail='auto', lastchange=NOW() WHERE id=?", array($id));
    db_query("INSERT INTO mail.virtual_mail_domains (domain, server) VALUES (?, ?)", array($id, $vmailserver));
  }
  elseif ($type == 'auto') {
    db_query("DELETE FROM mail.virtual_mail_domains WHERE domain=? AND hostname IS NULL LIMIT 1;", array($id));
    db_query("DELETE FROM mail.custom_mappings WHERE domain=? AND subdomain IS NULL LIMIT 1;", array($id));
    db_query("UPDATE kundendaten.domains SET mail='auto', lastchange=NOW() WHERE id=? LIMIT 1;", array($id));
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



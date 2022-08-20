<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/base.php');
require_once('inc/debug.php');

require_once('hasdomain.php');

require_once('common.php');


function forward_type($target)
{
    [$l, $d] = explode('@', strtolower($target), 2);
    DEBUG('Weiterleitung an '.$l.' @ '.$d);
    $result = db_query("SELECT id FROM kundendaten.domains WHERE CONCAT_WS('.', domainname, tld) = ?", [$d]);
    if ($result->rowCount() > 0) {
        // Lokale Domain
        return 'local';
    }
    // Auswärtige Domain aber keine aus der Liste
    return 'external';
}



function empty_account()
{
    $account = [
        'id' => null,
        'local' => '',
        'domain' => null,
        'password' => null,
        'enableextensions' => false,
    'smtpreply' => null,
    'quota' => config('vmail_basequota'),
    'quota_threshold' => 20,
        'forwards' => [],
        'autoresponder' => null,
        ];
    return $account;
}

function empty_autoresponder_config()
{
    $ar = [
    'valid_from' => date('Y-m-d'),
    'valid_until' => null,
    'fromname' => null,
    'fromaddr' => null,
    'subject' => null,
    'message' => 'Danke für Ihre E-Mail.
Ich bin aktuell nicht im Büro und werde Ihre Nachricht erst nach meiner Rückkehr beantworten.
Ihre E-Mail wird nicht weitergeleitet.',
    'quote' => null,
    ];
    return $ar;
}


function get_vmail_id_by_emailaddr($emailaddr)
{
    $result = db_query("SELECT id FROM mail.v_vmail_accounts WHERE CONCAT(local, '@', domainname) = ?", [$emailaddr]);
    $entry = $result->fetch();
    if ($entry === false) {
        return false;
    }
    return (int) $entry['id'];
}

function get_account_details($id, $checkuid = true)
{
    $id = (int) $id;
    $uid_check = '';
    DEBUG("checkuid: ".$checkuid);
    $args = [":id" => $id];
    if ($checkuid) {
        $uid = (int) $_SESSION['userinfo']['uid'];
        $uid_check = "useraccount=:uid AND ";
        $args[":uid"] = $uid;
    }
    $result = db_query("SELECT id, local, domain, password, enableextensions, smtpreply, forwards, autoresponder, server, quota, COALESCE(quota_used, 0) AS quota_used, quota_threshold from mail.v_vmail_accounts WHERE {$uid_check}id=:id LIMIT 1", $args);
    if ($result->rowCount() == 0) {
        system_failure('Ungültige ID oder kein eigener Account');
    }
    $acc = empty_account();
    $res = $result->fetch();
    foreach ($res as $key => $value) {
        if ($key == 'forwards') {
            continue;
        }
        $acc[$key] = $value;
    }
    if ($acc['forwards'] > 0) {
        $result = db_query("SELECT id, destination FROM mail.vmail_forward WHERE account=?", [$acc['id']]);
        while ($item = $result->fetch()) {
            array_push($acc['forwards'], ["id" => $item['id'], 'destination' => $item['destination']]);
        }
    }
    if ($acc['autoresponder'] > 0) {
        $result = db_query("SELECT id, IF(valid_from IS NULL OR valid_from > NOW() OR valid_until < NOW(), 0, 1) AS active, DATE(valid_from) AS valid_from, DATE(valid_until) AS valid_until, fromname, fromaddr, subject, message, quote FROM mail.vmail_autoresponder WHERE account=?", [$acc['id']]);
        $item = $result->fetch();
        DEBUG($item);
        $acc['autoresponder'] = $item;
    } else {
        $acc['autoresponder'] = null;
    }
    if ($acc['quota_threshold'] === null) {
        $acc['quota_threshold'] = -1;
    }
    return $acc;
}

function get_vmail_accounts()
{
    $uid = (int) $_SESSION['userinfo']['uid'];
    $result = db_query("SELECT * from mail.v_vmail_accounts WHERE useraccount=? ORDER BY domainname,local ASC", [$uid]);
    $ret = [];
    while ($line = $result->fetch()) {
        array_push($ret, $line);
    }
    DEBUG($ret);
    return $ret;
}



function get_vmail_domains()
{
    $uid = (int) $_SESSION['userinfo']['uid'];
    $result = db_query("SELECT id, domainname, server FROM mail.v_vmail_domains WHERE useraccount=? ORDER BY domainname", [$uid]);
    if ($result->rowCount() == 0) {
        system_failure('Sie haben keine Domains für virtuelle Mail-Verarbeitung');
    }
    $ret = [];
    while ($tmp = $result->fetch()) {
        array_push($ret, $tmp);
    }
    return $ret;
}


function find_account_id($accname)
{
    DEBUG($accname);
    $tmp = explode('@', $accname, 2);
    DEBUG($tmp);
    if (count($tmp) != 2) {
        system_failure("Der Account hat nicht die korrekte Syntax");
    }
    [$local, $domainname] = $tmp;

    $result = db_query("SELECT id FROM mail.v_vmail_accounts WHERE local=? AND domainname=? LIMIT 1", [$local, $domainname]);
    if ($result->rowCount() == 0) {
        system_failure("Der Account konnte nicht gefunden werden");
    }
    $tmp = $result->fetch();
    return $tmp[0];
}


function change_vmail_password($accname, $newpass)
{
    $accid = find_account_id($accname);
    $encpw = encrypt_mail_password($newpass);
    db_query("UPDATE mail.vmail_accounts SET password=:encpw WHERE id=:accid", [":encpw" => $encpw, ":accid" => $accid]);
}


function domainselect($selected = null, $selectattribute = '')
{
    $domainlist = get_vmail_domains();
    $selected = (int) $selected;

    $ret = '<select id="domain" name="domain" size="1" '.$selectattribute.' >';
    foreach ($domainlist as $dom) {
        $s = ($selected == $dom['id']) ? ' selected="selected" ' : '';
        $ret .= "<option value=\"{$dom['id']}\"{$s}>{$dom['domainname']}</option>\n";
    }
    $ret .= '</select>';
    return $ret;
}


function get_max_mailboxquota($server, $oldquota)
{
    $uid = (int) $_SESSION['userinfo']['uid'];
    $server = (int) $server;
    $result = db_query("SELECT systemquota - (COALESCE(systemquota_used,0) + COALESCE(mailquota,0)) AS free FROM system.v_quota WHERE uid=:uid AND server=:server", [":uid" => $uid, ":server" => $server]);
    $item = $result->fetch();
    if (! $item) {
        return $oldquota - config('vmail_basequota');
    }
    DEBUG("Free space: ".$item['free']." / Really: ".($item['free'] + ($oldquota - config('vmail_basequota'))));
    return max(0, $item['free'] + ($oldquota - config('vmail_basequota')));
}




function save_vmail_account($account)
{
    $accountlogin = ($_SESSION['role'] == ROLE_VMAIL_ACCOUNT);
    $id = $account['id'];
    if ($id != null) {
        $id = (int) $id;
        $oldaccount = get_account_details($id, !$accountlogin);
        // Erzeugt einen system_error() wenn ID ungültig
    }
    // Ab hier ist $id sicher, entweder NULL oder eine gültige ID des aktuellen users

    $newaccount = false;
    if ($id === null) {
        $newaccount = true;
    }

    $account['enableextensions'] = (int) (bool) $account['enableextensions'];
    if ($accountlogin) {
        if ($account['domain'] != $oldaccount['domain']) {
            system_failure('Sie können die E-Mail-Adresse nicht ändern!');
        }
        if ($account['local'] != $oldaccount['local']) {
            system_failure('Sie können die E-Mail-Adresse nicht ändern!');
        }
        if ($account['quota'] != $oldaccount['quota']) {
            system_failure('Sie können Ihren eigenen Speicherplatz nicht verändern.');
        }
        if ($account['smtpreply'] != null) {
            system_failure("Sie können nicht den Account stilllegen mit dem Sie grade angemeldet sind.");
        }
    } else {
        $account['local'] = filter_input_username($account['local']);
        if ($account['local'] == '') {
            system_failure('Die E-Mail-Adresse braucht eine Angabe vor dem »@«!');
            return false;
        }
        $account['domain'] = (int) $account['domain'];
        $domainlist = get_vmail_domains();
        $valid_domain = false;
        $domainname = null;
        $server = null;
        foreach ($domainlist as $dom) {
            if ($dom['id'] == $account['domain']) {
                $domainname = $dom['domainname'];
                $server = $dom['server'];
                $valid_domain = true;
                break;
            }
        }
        if (($account['domain'] == 0) || (! $valid_domain)) {
            system_failure('Bitte wählen Sie eine Ihrer Domains aus!');
            return false;
        }
        if ($id == null && get_vmail_id_by_emailaddr($account['local'].'@'.$domainname)) {
            system_failure('Diese E-Mail-Adresse gibt es bereits.');
            return false;
        }
    }

    $forwards = [];
    if (count($account['forwards']) > 0) {
        for ($i = 0 ; $i < count($account['forwards']) ; $i++) {
            if (! check_emailaddr($account['forwards'][$i]['destination'])) {
                system_failure('Das Weiterleitungs-Ziel »'.filter_output_html($account['forwards'][$i]['destination']).'« ist keine E-Mail-Adresse!');
            }
        }
    }

    if ($accountlogin) {
        $password = null;
        $set_password = false;
    } else {
        $password= null;
        if ($account['password'] != '') {
            $account['password'] = stripslashes($account['password']);
            $crack = strong_password($account['password']);
            if ($crack !== true) {
                system_failure('Ihr Passwort ist zu einfach. bitte wählen Sie ein sicheres Passwort!'."\nDie Fehlermeldung lautet: »{$crack}«");
                return false;
            }
            $password = encrypt_mail_password($account['password']);
        }
        $set_password = ($id == null || $password != null);
        if ($account['password'] === null) {
            $set_password=true;
        }
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
            if (isset($oldaccount) && $account['quota'] >= $oldaccount['quota'] && $newquota < $oldaccount['quota']) {
                # Wenn das Limit künstlich reduziert wurde, dann maximal auf den alten Wert.
                $newquota = $oldaccount['quota'];
            }
            warning("Ihr Speicherplatz reicht für diese Postfach-Größe nicht mehr aus. Ihr Postfach wurde auf {$newquota} MB reduziert. Bitte beachten Sie, dass damit Ihr Benutzerkonto keinen freien Speicherplatz mehr aufweist!");
        }

        $account['quota'] = $newquota;
    }

    if ($account['quota_threshold'] == -1) {
        $account['quota_threshold'] = null;
    } else {
        $account['quota_threshold'] = min((int) $account['quota_threshold'], (int) $account['quota']);
    }

    $account['local'] = strtolower($account['local']);
    # Leerstring wird zu NULL
    $account['smtpreply'] = ($account['smtpreply'] ? $account['smtpreply'] : null);

    $args = [":local" => $account['local'],
                ":domain" => $account['domain'],
                ":password" => $password,
                ":enableextensions" => $account['enableextensions'],
                ":smtpreply" => $account['smtpreply'],
                ":quota" => $account['quota'],
                ":quota_threshold" => $account['quota_threshold'],
                ":id" => $id,
                ];
    $query = '';
    if ($newaccount) {
        unset($args[":id"]);
        $query = "INSERT INTO mail.vmail_accounts (local, domain, password, enableextensions, smtpreply, quota, quota_threshold) VALUES (:local, :domain, :password, :enableextensions, :smtpreply, :quota, :quota_threshold)";
    } else {
        if ($set_password) {
            $pw=", password=:password";
        } else {
            unset($args[":password"]);
            $pw='';
        }
        $query = "UPDATE mail.vmail_accounts SET local=:local, domain=:domain{$pw}, enableextensions=:enableextensions, smtpreply=:smtpreply, quota=:quota, quota_threshold=:quota_threshold WHERE id=:id";
    }
    db_query($query, $args);
    if ($newaccount) {
        $id = db_insert_id();
    }

    if (is_array($account['autoresponder'])) {
        $ar = $account['autoresponder'];
        $quote = null;
        if ($ar['quote'] == 'attach') {
            $quote = "attach";
        } elseif ($ar['quote'] == 'inline') {
            $quote = 'inline';
        } elseif ($ar['quote'] == 'teaser') {
            $quote = 'teaser';
        }
        if (! check_emailaddr($ar['fromaddr'])) {
            input_error("Die Absenderadresse sieht ungültig aus. Es wird Ihre E-Mail-Adresse benutzt!");
            $ar['fromaddr'] = null;
        }
        $query = "REPLACE INTO mail.vmail_autoresponder (account, valid_from, valid_until, fromname, fromaddr, subject, message, quote) ".
             "VALUES (:id, :valid_from, :valid_until, :fromname, :fromaddr, :subject, :message, :quote)";
        $args = [":id" => $id,
                  ":valid_from" => $ar['valid_from'],
                  ":valid_until" => $ar['valid_until'],
                  ":fromname" => $ar['fromname'],
                  ":fromaddr" => $ar['fromaddr'],
                  ":subject" => $ar['subject'],
                  ":message" => $ar['message'],
                  ":quote" => $quote, ];
        db_query($query, $args);
    }



    if (! $newaccount) {
        db_query("DELETE FROM mail.vmail_forward WHERE account=?", [$id]);
    }

    if (count($account['forwards']) > 0) {
        $forward_query = "INSERT INTO mail.vmail_forward (account,destination) VALUES (:account, :destination)";
        for ($i=0;$i < count($account['forwards']); $i++) {
            if (! isset($account['forwards'][$i]['destination'])) {
                continue;
            }
            db_query($forward_query, [":account" => $id, ":destination" => $account['forwards'][$i]['destination']]);
        }
    }
    if ($newaccount && $password) {
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
        $result = db_query("SELECT useraccount, server, SUM(quota-(SELECT value FROM misc.config WHERE `key`='vmail_basequota')) AS quota, SUM(GREATEST(quota_used-(SELECT value FROM misc.config WHERE `key`='vmail_basequota'), 0)) AS used FROM mail.v_vmail_accounts WHERE useraccount=? GROUP BY useraccount, server", [$uid]);
        while ($line = $result->fetch()) {
            if ($line['quota'] !== null) {
                db_query("REPLACE INTO mail.vmailquota (uid, server, quota, used) VALUES (:uid, :server, :quota, :used)", [":uid" => $line['useraccount'], ":server" => $line['server'], ":quota" => $line['quota'], ":used" => $line['used']]);
            }
        }
    }

    return true;
}



function delete_account($id)
{
    $account = get_account_details($id);
    db_query("DELETE FROM mail.vmail_accounts WHERE id=?", [$account['id']]);
}



function domainsettings($only_domain=null)
{
    $uid = (int) $_SESSION['userinfo']['uid'];
    if ($only_domain) {
        $only_domain = (int) $only_domain;
    }
    $domains = [];
    $subdomains = [];

    // Domains
    $result = db_query("SELECT d.id, CONCAT_WS('.',d.domainname,d.tld) AS name, d.mail, d.mailserver_lock, m.id AS m_id, v.id AS v_id FROM kundendaten.domains AS d LEFT JOIN mail.virtual_mail_domains AS v ON (d.id=v.domain AND v.hostname IS NULL) LEFT JOIN mail.custom_mappings AS m ON (d.id=m.domain AND m.subdomain IS NULL) WHERE d.useraccount=:uid OR m.uid=:uid ORDER BY CONCAT_WS('.',d.domainname,d.tld);", [":uid" => $uid]);

    while ($mydom = $result->fetch()) {
        if (! array_key_exists($mydom['id'], $domains)) {
            if ($mydom['v_id']) {
                $mydom['mail'] = 'virtual';
            }
            $domains[$mydom['id']] = [
        "name" => $mydom['name'],
        "type" => $mydom['mail'],
        "mailserver_lock" => $mydom['mailserver_lock'],
        ];
            if ($only_domain && $only_domain == $mydom['id']) {
                return $domains[$only_domain];
            }
        }
    }

    // Subdomains
    $result = db_query("SELECT d.id, CONCAT_WS('.',d.domainname,d.tld) AS name, d.mail, m.id AS m_id, v.id AS v_id, IF(ISNULL(v.hostname),m.subdomain,v.hostname) AS hostname FROM kundendaten.domains AS d LEFT JOIN mail.virtual_mail_domains AS v ON (d.id=v.domain AND v.hostname IS NOT NULL) LEFT JOIN mail.custom_mappings AS m ON (d.id=m.domain AND m.subdomain IS NOT NULL) WHERE (m.id IS NOT NULL OR v.id IS NOT NULL) AND d.useraccount=:uid OR m.uid=:uid;", [":uid" => $uid]);
    while ($mydom = $result->fetch()) {
        if (! array_key_exists($mydom['id'], $subdomains)) {
            $subdomains[$mydom['id']] = [];
        }

        $type = 'auto';
        if ($mydom['v_id']) {
            $type = 'virtual';
        }
        $subdomains[$mydom['id']][] = [
      "name" => $mydom['hostname'],
      "type" => $type,
      ];
    }
    return ["domains" => $domains, "subdomains" => $subdomains];
}


function domain_has_vmail_accounts($domid)
{
    $domid = (int) $domid;
    $result = db_query("SELECT dom.id FROM mail.vmail_accounts AS acc LEFT JOIN mail.virtual_mail_domains AS dom ON (dom.id=acc.domain) WHERE dom.domain=?", [$domid]);
    return ($result->rowCount() > 0);
}


function change_domain($id, $type)
{
    $id = (int) $id;
    if (domain_has_vmail_accounts($id)) {
        system_failure("Sie müssen zuerst alle E-Mail-Konten mit dieser Domain löschen, bevor Sie die Webinterface-Verwaltung für diese Domain abschalten können.");
    }

    if (! in_array($type, ['none','auto','virtual'])) {
        system_failure("Ungültige Aktion");
    }

    $old = domainsettings($id);
    if ($old['type'] == $type) {
        system_failure('Domain ist bereits so konfiguriert');
    }

    if ($type == 'none') {
        db_query("DELETE FROM mail.virtual_mail_domains WHERE domain=? AND hostname IS NULL", [$id]);
        db_query("DELETE FROM mail.custom_mappings WHERE domain=? AND subdomain IS NULL", [$id]);
        db_query("UPDATE kundendaten.domains SET mail='none', lastchange=NOW() WHERE id=?", [$id]);
    } elseif ($type == 'virtual') {
        $vmailserver = (int) $_SESSION['userinfo']['server'];
        db_query("DELETE FROM mail.custom_mappings WHERE domain=? AND subdomain IS NULL", [$id]);
        db_query("UPDATE kundendaten.domains SET mail='auto', lastchange=NOW() WHERE id=?", [$id]);
        db_query("INSERT INTO mail.virtual_mail_domains (domain, server) VALUES (?, ?)", [$id, $vmailserver]);
    } elseif ($type == 'auto') {
        db_query("DELETE FROM mail.virtual_mail_domains WHERE domain=? AND hostname IS NULL LIMIT 1;", [$id]);
        db_query("DELETE FROM mail.custom_mappings WHERE domain=? AND subdomain IS NULL LIMIT 1;", [$id]);
        db_query("UPDATE kundendaten.domains SET mail='auto', lastchange=NOW() WHERE id=? LIMIT 1;", [$id]);
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

function maildomain_type($type)
{
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

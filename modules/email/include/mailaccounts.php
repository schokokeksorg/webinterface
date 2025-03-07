<?php

/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/debug.php');
require_once('inc/base.php');
require_once('inc/security.php');

require_once('class/domain.php');

function mailaccounts($uid)
{
    $uid = (int) $uid;
    $result = db_query("SELECT m.id,concat_ws('@',`m`.`local`,if(isnull(`m`.`domain`),:masterdomain,`d`.`domainname`)) AS `account`, `m`.`password` AS `cryptpass`,`m`.`maildir` AS `maildir`,aktiv from (`mail`.`mailaccounts` `m` left join `mail`.`v_domains` `d` on((`d`.`id` = `m`.`domain`))) WHERE m.uid=:uid ORDER BY if(isnull(`m`.`domain`),:masterdomain,`d`.`domainname`), local", [":masterdomain" => config("masterdomain"), ":uid" => $uid]);
    DEBUG("Found " . @$result->rowCount() . " rows!");
    $accounts = [];
    if (@$result->rowCount() > 0) {
        while ($acc = @$result->fetch(PDO::FETCH_OBJ)) {
            array_push($accounts, ['id' => $acc->id, 'account' => $acc->account, 'mailbox' => $acc->maildir, 'cryptpass' => $acc->cryptpass, 'enabled' => ($acc->aktiv == 1)]);
        }
    }
    return $accounts;
}

function get_mailaccount($id)
{
    $id = (int) $id;
    $uid = (int) $_SESSION['userinfo']['uid'];
    $result = db_query("SELECT concat_ws('@',`m`.`local`,if(isnull(`m`.`domain`),:masterdomain,`d`.`domainname`)) AS `account`, `m`.`password` AS `cryptpass`,`m`.`maildir` AS `maildir`,aktiv from (`mail`.`mailaccounts` `m` left join `mail`.`v_domains` `d` on((`d`.`id` = `m`.`domain`))) WHERE m.id=:mid AND m.uid=:uid", [":masterdomain" => config("masterdomain"), ":uid" => $uid, ":mid" => $id]);
    DEBUG("Found " . $result->rowCount() . " rows!");
    if ($result->rowCount() != 1) {
        system_failure('Dieser Mailaccount existiert nicht oder gehört Ihnen nicht');
    }
    $acc = $result->fetch(PDO::FETCH_OBJ);
    $ret = ['account' => $acc->account, 'mailbox' => $acc->maildir,  'enabled' => ($acc->aktiv == 1)];
    DEBUG(print_r($ret, true));
    return $ret;
}

function change_mailaccount($id, $arr)
{
    $id = (int) $id;
    $uid = (int) $_SESSION['userinfo']['uid'];
    $conditions = [];
    $values = [":id" => $id, ":uid" => $uid];

    if (isset($arr['account'])) {
        [$local, $domain] = explode('@', $arr['account'], 2);
        if ($domain == config('masterdomain')) {
            $values[':domain'] = null;
        } else {
            $domain = new Domain((string) $domain);
            if ($domain->id == null) {
                $values[":domain"] = null;
            } else {
                $domain->ensure_userdomain();
                $values[":domain"] = $domain->id;
            }
        }
        $values[":local"] = $local;
        $conditions[] = "local=:local";
        $conditions[] = "domain=:domain";
    }
    if (isset($arr['mailbox'])) {
        array_push($conditions, "`maildir`=:maildir");
        if ($arr['mailbox'] == '') {
            $values[":maildir"] = null;
        } else {
            $values[":maildir"] = $arr['mailbox'];
        }
    }

    if (isset($arr['password'])) {
        $result = strong_password($arr['password']);
        if ($result !== true) {
            system_failure("Unsicheres Passwort: " . $result);
        }
        $encpw = gen_pw_hash($arr['password']);
        array_push($conditions, "`password`=:password");
        $values[":password"] = $encpw;
    }

    if (isset($arr['enabled'])) {
        array_push($conditions, "`aktiv`=:aktiv");
        $values[":aktiv"] = ($arr['enabled'] == 'Y' ? 1 : 0);
    }


    db_query("UPDATE mail.mailaccounts SET " . implode(",", $conditions) . " WHERE id=:id AND uid=:uid", $values);
    logger(LOG_INFO, "modules/imap/include/mailaccounts", "imap", "updated account »{$id}«");
}

function create_mailaccount($arr)
{
    $values = [];

    if (($arr['account']) == '') {
        system_failure('empty account name!');
    }

    $values[':uid'] = (int) $_SESSION['userinfo']['uid'];

    [$local, $domain] = explode('@', $arr['account'], 2);
    if ($domain == config('masterdomain')) {
        $values[':domain'] = null;
    } else {
        $domain = new Domain((string) $domain);
        if ($domain->id == null) {
            $values[':domain'] = null;
        } else {
            $domain->ensure_userdomain();
            $values[':domain'] = $domain->id;
        }
    }

    $values[':local'] = $local;

    if (isset($arr['mailbox'])) {
        if ($arr['mailbox'] == '') {
            $values[':maildir'] = null;
        } else {
            $values[':maildir'] = $arr['mailbox'];
        }
    }


    if (isset($arr['password'])) {
        $result = strong_password($arr['password']);
        if ($result !== true) {
            system_failure("Unsicheres Passwort: " . $result);
        }
        $values[':password'] = gen_pw_hash($arr['password']);
    }

    if (isset($arr['enabled'])) {
        $values[':aktiv'] = ($arr['enabled'] == 'Y' ? 1 : 0);
    }


    $fields = array_map(function ($k) {
        return substr($k, 1);
    }, array_keys($values));
    db_query("INSERT INTO mail.mailaccounts (" . implode(',', $fields) . ") VALUES (" . implode(",", array_keys($values)) . ")", $values);
    logger(LOG_INFO, "modules/imap/include/mailaccounts", "imap", "created account »{$arr['account']}«");
}


function get_mailaccount_id($accountname)
{
    [$local, $domain] = explode('@', $accountname, 2);

    $args = [":local" => $local,
        ":domain" => $domain, ];

    $result = db_query("SELECT acc.id FROM mail.mailaccounts AS acc LEFT JOIN mail.v_domains AS dom ON (dom.id=acc.domain) WHERE local=:local AND dom.domainname=:domain", $args);
    if (($result->rowCount() == 0) && ($domain == config('masterdomain'))) {
        unset($args[':domain']);
        $result = db_query("SELECT acc.id FROM mail.mailaccounts AS acc WHERE local=:local AND acc.domain IS NULL", $args);
    }
    if ($result->rowCount() != 1) {
        system_failure('account nicht eindeutig');
    }
    $acc = $result->fetch();
    return $acc['id'];
}


function delete_mailaccount($id)
{
    $id = (int) $id;
    db_query("DELETE FROM mail.mailaccounts WHERE id=?", [$id]);
    logger(LOG_INFO, "modules/imap/include/mailaccounts", "imap", "deleted account »{$id}«");
}


function check_valid($acc)
{
    $user = $_SESSION['userinfo'];
    DEBUG("Account-data: " . print_r($acc, true));
    DEBUG("User-data: " . print_r($user, true));
    if ($acc['mailbox'] != '') {
        if (substr($acc['mailbox'], 0, strlen($user['homedir']) + 1) != $user['homedir'] . '/') {
            return "Die Mailbox muss innerhalb des Home-Verzeichnisses liegen. Sie haben »" . $acc['mailbox'] . "« als Mailbox angegeben, Ihr Home-Verzeichnis ist »" . $user['homedir'] . "/«.";
        }
        if (!check_path($acc['mailbox'])) {
            return "Sie verwenden ungültige Zeichen in Ihrem Mailbox-Pfad.";
        }
    }

    if ($acc['account'] == '' || strpos($acc['account'], '@') == 0) {
        return "Es wurde kein Benutzername angegeben!";
    }
    if (strpos($acc['account'], '@') === false) {
        return "Es wurde kein Domain-Teil im Account-Name angegeben. Account-Namen müssen einen Domain-Teil enthalten. Im Zweifel versuchen Sie »@" . config('masterdomain') . "«.";
    }

    [$local, $domain] = explode('@', $acc['account'], 2);
    verify_input_username($local);
    $tmpdomains = get_domain_list($user['customerno'], $user['uid']);
    $domains = [];
    foreach ($tmpdomains as $dom) {
        $domains[] = $dom->fqdn;
    }

    if (array_search($domain, $domains) === false) {
        if ($domain == config('masterdomain')) {
            if (substr($local, 0, strlen($user['username'])) != $user['username'] || ($acc['account'][strlen($user['username'])] != '-' && $acc['account'][strlen($user['username'])] != '@')) {
                return "Sie haben »@" . config('masterdomain') . "« als Domain-Teil angegeben, aber der Benutzer-Teil beginnt nicht mit Ihrem Benutzername!";
            }
        } else {
            return "Der angegebene Domain-Teil (»" . htmlentities($domain, ENT_QUOTES, "UTF-8") . "«) ist nicht für Ihren Account eingetragen. Sollte dies ein Fehler sein, wenden sie sich bitte an einen Administrator!";
        }
    }

    return '';
}


function imap_on_vmail_domain()
{
    $uid = (int) $_SESSION['userinfo']['uid'];
    $result = db_query("SELECT m.id FROM mail.mailaccounts AS m INNER JOIN mail.virtual_mail_domains AS vd USING (domain) WHERE vd.hostname IS NULL AND m.uid=?", [$uid]);
    if ($result->rowCount() > 0) {
        return true;
    }
    return false;
}

function user_has_only_vmail_domains()
{
    $uid = (int) $_SESSION['userinfo']['uid'];
    $result = db_query("SELECT id FROM mail.v_vmail_domains WHERE useraccount=?", [$uid]);
    // User hat keine VMail-Domains
    if ($result->rowCount() == 0) {
        return false;
    }
    $result = db_query("SELECT d.id FROM mail.v_domains AS d LEFT JOIN mail.v_vmail_domains AS vd USING (domainname) WHERE vd.id IS NULL AND d.user=?", [$uid]);
    // User hat keine Domains die nicht vmail-Domains sind
    if ($result->rowCount() == 0) {
        return true;
    }
    return false;
}

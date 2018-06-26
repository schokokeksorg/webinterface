<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2018 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once("inc/debug.php");
require_once("inc/security.php");

require_once('class/domain.php');

function get_jabber_accounts()
{
    require_role(ROLE_CUSTOMER);
    $customerno = (int) $_SESSION['customerinfo']['customerno'];
    $result = db_query("SELECT id, `create`, created, lastactivity, local, domain FROM jabber.accounts WHERE customerno=? AND `delete`=0", array($customerno));
    $accounts = array();
    if (@$result->rowCount() > 0) {
        while ($acc = @$result->fetch()) {
            array_push($accounts, $acc);
        }
    }
    return $accounts;
}



function get_jabberaccount_details($id)
{
    require_role(ROLE_CUSTOMER);
    $args = array(":customerno" => $_SESSION['customerinfo']['customerno'],
                ":id" => $id);

    $result = db_query("SELECT id, local, domain FROM jabber.accounts WHERE customerno=:customerno AND id=:id", $args);
    if ($result->rowCount() != 1) {
        system_failure("Invalid account");
    }
    $data = $result->fetch();
    if ($data['domain'] == null) {
        $data['domain'] = config('masterdomain');
    } else {
        $dom = new Domain((int) $data['domain']);
        $dom->ensure_customerdomain();
        $data['domain'] = $dom->fqdn;
    }
    return $data;
}


function valid_jabber_password($pass)
{
    // Hier könnten erweiterte Checks stehen wenn nötig.
    /*$foo = ereg_replace('["\']', '', $pass);
    DEBUG("\$foo = {$foo} / \$pass = {$pass}");
    return ($foo == $pass);
    */
    return true;
}


function create_jabber_account($local, $domain, $password)
{
    require_role(ROLE_CUSTOMER);
    $data = array(":customerno" => $_SESSION['customerinfo']['customerno'],
                ":local" => filter_input_username($local),
                ":domain" => $domain);
    if (! valid_jabber_password($password)) {
        input_error('Das Passwort enthält Zeichen, die aufgrund technischer Beschränkungen momentan nicht benutzt werden können.');
        return;
    }
    $data[':password'] = $password;

    if ($domain > 0) {
        $args = array(":domain" => $data[":domain"], ":customerno" => $data[":customerno"]);
        $result = db_query("SELECT id FROM kundendaten.domains WHERE kunde=:customerno AND jabber=1 AND id=:domain", $args);
        if ($result->rowCount() == 0) {
            logger(LOG_WARNING, "modules/jabber/include/jabberaccounts", "jabber", "attempt to create account for invalid domain »{$domain}«");
            system_failure("Invalid domain!");
        }
    }

    $args = array(":domain" => $data[":domain"], ":local" => $data[":local"]);
    $domainquery = "domain=:domain";
    if ($domain == 0) {
        unset($args[":domain"]);
        $data[":domain"] = null;
        $domainquery = 'domain IS NULL';
    }
    $result = db_query("SELECT id FROM jabber.accounts WHERE local=:local AND {$domainquery}", $args);
    if ($result->rowCount() > 0) {
        logger(LOG_WARNING, "modules/jabber/include/jabberaccounts", "jabber", "attempt to create already existing account »{$local}@{$domain}«");
        system_failure("Diesen Account gibt es bereits!");
    }

    db_query("INSERT INTO jabber.accounts (customerno,local,domain,password) VALUES (:customerno, :local, :domain, :password);", $data);
    logger(LOG_INFO, "modules/jabber/include/jabberaccounts", "jabber", "created account »{$local}@{$domain}«");
}



function change_jabber_password($id, $password)
{
    require_role(ROLE_CUSTOMER);
    if (! valid_jabber_password($password)) {
        input_error('Das Passwort enthält Zeichen, die aufgrund technischer Beschränkungen momentan nicht benutzt werden können.');
        return;
    }
    $args = array(":customerno" => $_SESSION['customerinfo']['customerno'],
                ":id" => $id,
                ":password" => $password);

    db_query("UPDATE jabber.accounts SET password=:password WHERE customerno=:customerno AND id=:id", $args);
    logger(LOG_INFO, "modules/jabber/include/jabberaccounts", "jabber", "changed password for account  »{$id}«");
}



function delete_jabber_account($id)
{
    require_role(ROLE_CUSTOMER);

    $args = array(":customerno" => $_SESSION['customerinfo']['customerno'],
                ":id" => $id);

    db_query("UPDATE jabber.accounts SET `delete`=1 WHERE customerno=:customerno AND id=:id", $args);
    logger(LOG_INFO, "modules/jabber/include/jabberaccounts", "jabber", "deleted account »{$id}«");
}


function new_jabber_domain($id)
{
    $d = new Domain((int) $id);
    $d->ensure_customerdomain();
    db_query("UPDATE kundendaten.domains SET jabber=2 WHERE jabber=0 AND id=?", array($d->id));
}

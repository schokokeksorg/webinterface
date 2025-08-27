<?php

/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

function whitelist_entries()
{
    $uid = (int) $_SESSION['userinfo']['uid'];
    $res = db_query("SELECT id,local,domain,date,expire FROM mail.greylisting_manual_whitelist WHERE uid=?", [$uid]);
    $return = [];
    while ($line = $res->fetch()) {
        array_push($return, $line);
    }
    return $return;
}


function get_whitelist_details($id)
{
    $args = [":id" => $id,
        ":uid" => $_SESSION['userinfo']['uid'], ];
    $res = db_query("SELECT id,local,domain,date,expire FROM mail.greylisting_manual_whitelist WHERE uid=:uid AND id=:id", $args);
    if ($res->rowCount() != 1) {
        system_failure('Kann diesen Eintrag nicht finden');
    }
    return $res->fetch();
}


function delete_from_whitelist($id)
{
    $id = (int) $id;
    // Check if the ID is valid: This will die if not.
    $entry = get_whitelist_details($id);

    db_query("DELETE FROM mail.greylisting_manual_whitelist WHERE id=?", [$id]);
}


function valid_entry($local, $domain)
{
    if ($domain == 'schokokeks.org') {
        if (($local != $_SESSION['userinfo']['username'])
            && (strpos($local, $_SESSION['userinfo']['username'] . '-') !== 0)) {
            system_failure('Diese E-Mail-Adresse gehört Ihnen nicht!');
        }
        return true;
    }
    $args = [":domain" => $domain,
        ":uid" => $_SESSION['userinfo']['uid'], ];
    $res = db_query("SELECT id FROM mail.v_domains WHERE domainname=:domain AND user=:uid", $args);
    if ($res->rowCount() != 1) {
        system_failure('Diese domain gehört Ihnen nicht!');
    }
    return true;
}


function new_whitelist_entry($local, $domain, $minutes)
{
    valid_entry($local, $domain);
    $args = [":uid" => $_SESSION['userinfo']['uid'],
        ":local" => $local,
        ":domain" => $domain, ];

    $expire = 'NULL';
    if ($minutes == 'none') {
        $expire = 'NULL';
    } else {
        $args[':minutes'] = $minutes;
        $expire = "NOW() + INTERVAL :minutes MINUTE";
    }
    db_query("INSERT INTO mail.greylisting_manual_whitelist (local,domain,date,expire,uid) VALUES "
             . "(:local, :domain, NOW(), {$expire}, :uid)", $args);
}

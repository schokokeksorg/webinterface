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

function whitelist_entries()
{
    $uid = (int) $_SESSION['userinfo']['uid'];
    $res = db_query("SELECT id,local,domain,date,expire FROM mail.greylisting_manual_whitelist WHERE uid=?", array($uid));
    $return = array();
    while ($line = $res->fetch()) {
        array_push($return, $line);
    }
    return $return;
}


function get_whitelist_details($id)
{
    $args = array(":id" => $id,
                ":uid" => $_SESSION['userinfo']['uid']);
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

    db_query("DELETE FROM mail.greylisting_manual_whitelist WHERE id=?", array($id));
}


function valid_entry($local, $domain)
{
    if ($domain == 'schokokeks.org') {
        if (($local != $_SESSION['userinfo']['username']) &&
            (strpos($local, $_SESSION['userinfo']['username'].'-') !== 0)) {
            system_failure('Diese E-Mail-Adresse gehört Ihnen nicht!');
        }
        return true;
    }
    $args = array(":domain" => $domain,
                ":uid" => $_SESSION['userinfo']['uid']);
    $res = db_query("SELECT id FROM mail.v_domains WHERE domainname=:domain AND user=:uid", $args);
    if ($res->rowCount() != 1) {
        system_failure('Diese domain gehört Ihnen nicht!');
    }
    return true;
}


function new_whitelist_entry($local, $domain, $minutes)
{
    valid_entry($local, $domain);
    $args = array(":uid" => $_SESSION['userinfo']['uid'],
                ":local" => $local,
                ":domain" => $domain);
    
    $expire = 'NULL';
    if ($minutes == 'none') {
        $expire = 'NULL';
    } else {
        $args[':minutes'] = $minutes;
        $expire = "NOW() + INTERVAL :minutes MINUTE";
    }
    db_query("INSERT INTO mail.greylisting_manual_whitelist (local,domain,date,expire,uid) VALUES ".
             "(:local, :domain, NOW(), {$expire}, :uid)", $args);
}

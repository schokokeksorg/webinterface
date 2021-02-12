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

require_once('inc/base.php');
require_once('inc/debug.php');
require_once('inc/security.php');


function get_lists($filter)
{
    $uid = (int) $_SESSION['userinfo']['uid'];
    $result = null;
    if ($filter) {
        $filter = '%'.$filter.'%';
        $result = db_query("SELECT id, created, status, listname, fqdn, urlhost, admin, archivesize, subscribers, lastactivity, backend FROM mail.v_mailman_lists WHERE status!='deleted' AND owner=:uid AND (listname LIKE :filter OR fqdn LIKE :filter OR admin LIKE :filter) ORDER BY listname", array('uid' => $uid, 'filter' => $filter));
    } else {
        $result = db_query("SELECT id, created, status, listname, fqdn, urlhost, admin, archivesize, subscribers, lastactivity, backend FROM mail.v_mailman_lists WHERE status!='deleted' AND owner=:uid ORDER BY listname", array('uid' => $uid));
    }
    $ret = array();
    while ($list = $result->fetch()) {
        $ret[] = $list;
    }
    DEBUG($ret);
    return $ret;
}


function get_list($id)
{
    $args = array(":id" => $id,
                ":uid" => $_SESSION['userinfo']['uid']);
    $result = db_query("SELECT id, created, status, listname, fqdn, urlhost, admin, archivesize, subscribers, lastactivity, backend FROM mail.v_mailman_lists WHERE owner=:uid AND id=:id", $args);
    if ($result->rowCount() < 1) {
        system_failure('Die gewünschte Mailingliste konnte nicht gefunden werden');
    }
    $list = $result->fetch();
    DEBUG($list);

    return $list;
}


function delete_list($id)
{
    $args = array(":id" => $id,
                ":uid" => $_SESSION['userinfo']['uid']);
    db_query("UPDATE mail.mailman_lists SET status='delete' WHERE owner=:uid AND id=:id", $args);
}

function request_new_password($id)
{
    $args = array(":id" => $id,
                ":uid" => $_SESSION['userinfo']['uid']);
    db_query("UPDATE mail.mailman_lists SET status='newpw' WHERE owner=:uid AND id=:id", $args);
}

function create_list($listname, $maildomain, $admin)
{
    $listname = strtolower($listname);
    verify_input_username($listname);
    if (in_array($listname, array("admin", "administrator", "webmaster", "hostmaster", "postmaster"))) {
        system_failure('Der Mailinglistenname '.$listname.' ist unzulässig.');
    }
    if (! check_emailaddr($admin)) {
        system_failure('Der Verwalter muss eine gültige E-Mail-Adresse sein ('.$admin.').');
    }
    # FIXME: Zukünftig soll diese Beschränkung weg fallen!
    $result = db_query("SELECT id FROM mail.mailman_lists WHERE listname LIKE ?", array($listname));
    if ($result->rowCount() > 0) {
        system_failure('Eine Liste mit diesem Namen existiert bereits auf unserem Mailinglisten-Server (unter einer Ihrer Domains oder unter einer Domain eines anderen Kunden). Jeder Listenname kann auf dem gesamten Server nur einmal verwendet werden.');
    }

    $args = array(":listname" => $listname,
                ":maildomain" => $maildomain,
                ":owner" => $_SESSION['userinfo']['uid'],
                ":admin" => $admin);

    db_query("INSERT INTO mail.mailman_lists (status, listname, maildomain, owner, admin) VALUES ('pending', :listname, :maildomain, :owner, :admin)", $args);
    DEBUG('Neue ID: '.db_insert_id());
}

function get_possible_mailmandomains()
{
    DEBUG('get_possible_mailmandomains()');
    $uid = (int) $_SESSION['userinfo']['uid'];
    $result = db_query("SELECT d.id, CONCAT_WS('.',d.domainname,d.tld) AS fqdn, m.backend AS backend FROM kundendaten.domains AS d LEFT JOIN mail.mailman_domains AS m ON (m.domain=d.id) WHERE d.useraccount=:uid AND m.id IS NULL ORDER BY CONCAT_WS('.',d.domainname,d.tld)", array(":uid" => $uid));
    $ret = array();
    while ($dom = $result->fetch()) {
        $ret[] = $dom;
    }
    DEBUG($ret);
    return $ret;
}


function insert_mailman_domain($subdomain, $domainid, $backend = 'mailman')
{
    DEBUG("insert_mailman_domain($subdomain, $domainid, $backend)");
    $possible = get_possible_mailmandomains();
    $found = false;
    foreach ($possible as $dom) {
        if ($domainid == $dom['id']) {
            $found = true;
        }
    }
    if (! $found) {
        system_failue('invalid domain id');
    }
    db_query("INSERT INTO mail.mailman_domains (hostname, domain, backend) VALUES (:hostname, :domain, :backend)", array(":hostname" => $subdomain, ":domain" => $domainid, ":backend" => $backend));
    return db_insert_id();
}


function lists_on_domain($domainid)
{
    DEBUG("lists_on_domain()");
    $result = db_query("SELECT id, listname FROM mail.mailman_lists WHERE status != 'delete' AND status != 'deleted' AND maildomain=(SELECT id FROM mail.mailman_domains WHERE domain=?)", array($domainid));
    $ret = array();
    while ($l = $result->fetch()) {
        $ret[] = $l;
    }
    return $ret;
}


function delete_mailman_domain($domainid)
{
    DEBUG("delete_mailman_domain()");
    $lists = lists_on_domain($domainid);
    if (count($lists) > 0) {
        system_failure("Es gibt noch Mailinglisten unter diesem Domainnamen, er kann daher nicht gelöscht werden");
    } else {
        db_query("DELETE FROM mail.mailman_domains WHERE domain=? AND (SELECT COUNT(*) FROM mail.mailman_lists WHERE maildomain=mail.mailman_domains.id)=0;", array($domainid));
    }
}

function get_mailman_domains()
{
    DEBUG('get_mailman_domains()');
    $uid = (int) $_SESSION['userinfo']['uid'];
    $result = db_query("SELECT md.id, md.fqdn, md.is_webhost, md.backend FROM mail.v_mailman_domains AS md left join mail.v_domains AS d on (d.id=md.domain) where d.user=?", array($uid));
    $ret = array();
    while ($dom = $result->fetch()) {
        $ret[] = $dom;
    }
    DEBUG($ret);
    return $ret;
}

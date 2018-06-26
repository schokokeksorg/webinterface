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
require_once('inc/security.php');
require_once('inc/debug.php');

require_once('session/start.php');
require_once('su.php');

require_role(ROLE_CUSTOMER);

if (isset($_GET['uid'])) {
    $uid = (int) $_GET['uid'];
    $token = $_GET['token'];
    $cid = (int) $_SESSION['customerinfo']['customerno'];
    $users = find_users_for_customer($cid);
    $found = false;
    foreach ($users as $u) {
        if ($uid == $u['uid']) {
            $found = true;
        }
    }
    if (! $found) {
        system_failure('Unerlaubter Useraccount');
    }

    if (!isset($_SESSION['su_customer_timestamp']) || $_SESSION['su_customer_timestamp'] < time() - 30) {
        system_failure("Aus Sicherheitsgründen muss die Auswahl auf dieser Seite innerhalb von 30 Sekunden getroffen werden.");
    }

    if (!isset($_SESSION['su_customer_token']) || $_SESSION['su_customer_token'] != $token) {
        system_failure("Ungültige Reihenfolge der Aufrufe");
    }

    su('u', $uid);
}

title("Benutzer wechseln");

output('<p>Hiermit können Sie das Webinterface mit den Rechten eines beliebigen anderen Benutzers aus Ihrem Kundenaccount benutzen.</p>
');

$token = random_string(20);
$_SESSION['su_customer_token'] = $token;
$_SESSION['su_customer_timestamp'] = time();

$cid = (int) $_SESSION['customerinfo']['customerno'];
$users = find_users_for_customer($cid);

output('<p>Zu Ihrem Kundenkonto gehören die folgenden Benutzer. Klicken Sie einen Benutzernamen an um zu diesem zu wechseln.</p><ul>');

foreach ($users as $u) {
    if ($u['uid'] == $_SESSION['userinfo']['uid']) {
        output("<li>{$u['username']} - (Eigener Benutzeraccount)</li>");
        continue;
    }
    $realname = $u['name'];
    if ($realname) {
        $realname = ' - '.$realname;
    }
    output("<li>".internal_link('', "{$u['username']}{$realname}", "uid={$u['uid']}&token={$token}")."</li>");
}
output('</ul>');

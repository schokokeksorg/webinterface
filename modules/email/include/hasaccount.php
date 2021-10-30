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

function user_has_accounts()
{
    $uid = (int) $_SESSION['userinfo']['uid'];
    $result = db_query("SELECT id from `mail`.`mailaccounts` WHERE uid=?", [$uid]);
    DEBUG($result->rowCount()." accounts");
    return ($result->rowCount() > 0);
}

if (! function_exists("user_has_vmail_domain")) {
    function user_has_vmail_domain()
    {
        $role = $_SESSION['role'];
        if (! ($role & ROLE_SYSTEMUSER)) {
            return false;
        }
        $uid = (int) $_SESSION['userinfo']['uid'];
        $result = db_query("SELECT COUNT(*) FROM mail.v_vmail_domains WHERE useraccount=?", [$uid]);
        $row = $result->fetch();
        $count = $row[0];
        DEBUG("User has {$count} vmail-domains");
        return ((int) $count > 0);
    }
}

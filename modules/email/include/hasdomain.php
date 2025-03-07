<?php

/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

if (!function_exists("user_has_vmail_domain")) {
    function user_has_vmail_domain()
    {
        $role = $_SESSION['role'];
        if (!($role & ROLE_SYSTEMUSER)) {
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

if (!function_exists("user_has_dotcourier_domain")) {
    function user_has_dotcourier_domain()
    {
        $role = $_SESSION['role'];
        if (!($role & ROLE_SYSTEMUSER)) {
            return false;
        }
        $uid = (int) $_SESSION['userinfo']['uid'];
        $result = db_query("select 1 from mail.custom_mappings as c left join mail.v_domains as d on (d.id=c.domain) where d.user=:uid or c.uid=:uid UNION " .
            "SELECT 1 FROM mail.v_domains AS d WHERE d.user=:uid AND d.mail != 'none' AND d.id != ALL(SELECT domain FROM mail.virtual_mail_domains)", [":uid" => $uid]);
        $ret = ($result->rowCount() > 0);
        if ($ret) {
            DEBUG("User {$uid} has dotcourier-domains");
            return true;
        }
        return false;
    }
}

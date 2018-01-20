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

require_once('inc/debug.php');
require_once('inc/icons.php');

require_once('class/domain.php');
require_once('domains.php');
require_once('modules/contacts/include/contacts.php');

require_role(ROLE_CUSTOMER);

if (!isset($_REQUEST['id'])) {
    system_failure("Ungültiger Aufruf!");
}


$dom = new Domain( (int) $_REQUEST['id']);

title("Domain {$dom->fqdn}");
$section = 'domains_domains';

if ($dom->provider == 'external') {
    output("<p>Diese Domain ist extern registriert!</p>");
}
if ($dom->provider == 'terions') {
    output("<p>Folgende Informationen sind bei dieser Domain hinterlegt:</p>");
    if ($dom->owner && $dom->admin_c) {
        $descr = 'Inhaber (OWNER)';
        if ($dom->owner == $dom->admin_c) {
            $descr = 'Inhaber';
        }
        $owner = get_contact($dom->owner);
        $name = nl2br(filter_input_general($owner['name']));
        if ($owner['company']) {
            $name = nl2br(filter_input_general($owner['company']))."<br>\n".$name;
        }
        output("<p>{$descr}:<br><strong>{$name}</strong><br>(Adresse #{$owner['id']})<br>".internal_link("../contacts/edit", icon_edit()." Adresse bearbeiten", "back=".urlencode('../domains/detail?id='.$dom->id)."&id=".$owner['id'])."</p>");

        if ($dom->owner != $dom->admin_c) {
            $admin_c = get_contact($dom->admin_c);
            $name = nl2br(filter_input_general($admin_c['name']));
            if ($admin_c['company']) {
                $name = nl2br(filter_input_general($admin_c['company']))."<br>\n".$name;
            }
            output("<p>Verwalter (ADMIN_C):<br><strong>{$name}</strong><br>(Adresse #{$admin_c['id']})<br>".internal_link("../contacts/edit", icon_edit()." Adresse bearbeiten", "back=".urlencode('../domains/detail?id='.$dom->id)."&id=".$admin_c['id'])."</p>");
        }

        #addnew("ownchange", "Inhaber dieser Domain ändern", "id=".$dom->id."&back=".urlencode('detail?id='.$dom->id));

    } else {
        output('<p>Die Inhaberdaten dieser Domain können nicht ausgelesen werden. Bitte wenden Sie sich für Änderungen an den Support!</p>');
    }
}
output("");

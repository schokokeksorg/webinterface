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
require_once('inc/security.php');
require_once('inc/icons.php');

require_role(ROLE_SYSTEMUSER);

require_once('dnsinclude.php');


$dyndns = get_dyndns_accounts();

title("DynDNS-Accounts");
output("<p>Hier sehen Sie eine Übersicht über die angelegten DynDNS-Accounts.</p>");

output('<table><tr><th>Kürzel</th><th>Methode</th><th>aktuelle IP</th><th>letztes Update</th><th>&#160;</th></tr>
');

foreach ($dyndns as $entry) {
    $handle = $entry['handle'];
    if (!$handle) {
        $handle = '<em>undefiniert</em>';
    }
    $method = '';
    if ($entry['sshkey']) {
        if ($entry['password']) {
            $method = 'SSH, HTTP';
        } else {
            $method = 'SSH';
        }
    } elseif ($entry['password']) {
        $method = 'HTTP';
    } else {
        $method = '<em>keine</em>';
    }
    $address = $entry['address'];
    if ($address == '') {
        $address = "<em>keine IPv4-Adresse</em>";
    }
    if ($entry['ipv6']) {
        $address .= " / " . $entry['ipv6'];
    }
    output("<tr><td>" . internal_link('dyndns_edit', $handle, "id={$entry['id']}") . "</td><td>{$method}</td><td>{$address}</td><td>{$entry['lastchange']}</td><td>" . internal_link('save', icon_delete("Account löschen"), "id={$entry['id']}&type=dyndns&action=delete") . ' ' . internal_link('dyndns_hostnames', other_icon("world_link.png", "Hostnames verwalten"), "id={$entry['id']}") . "</td></tr>\n");
}
output('</table>');

addnew('dyndns_edit', 'Neuen DynDNS-Account anlegen');

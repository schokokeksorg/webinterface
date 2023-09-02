<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('session/start.php');

require_once('class/domain.php');
require_once('jabberaccounts.php');

require_once('inc/security.php');
require_once('inc/icons.php');

require_role(ROLE_CUSTOMER);

$jabberaccounts = get_jabber_accounts();

title("Jabber-Accounts");

output("<table>");

foreach ($jabberaccounts as $acc) {
    $not_ready = '';
    if ($acc['create'] == 1) {
        $not_ready = " ".icon_warning('Dieser Account wird in Kürze auf dem Server eingerichtet.');
    }
    $lastactivity = $acc['lastactivity'];
    // Innerhalb der letzten Woche verwendet
    if ($lastactivity > strftime('%Y-%m-%d', time() - 7 * 24 * 60 * 60)) {
        $lastactivity = 'Kürzlich';
    }
    if (! $lastactivity) {
        $lastactivity = 'Bisher nie verwendet';
    }
    $local = filter_output_html($acc['local']);
    $domain = new Domain((int) $acc['domain']);
    if ($domain->id == null) {
        $domain = new Domain();
        $domain->fqdn = config('masterdomain');
    }
    output("<tr><td>{$local}@{$domain->fqdn}{$not_ready}<br /><span style=\"font-size: 80%; font-style: italic;\">Letzte Nutzung: {$lastactivity}</span></td><td>".internal_link('chpass', icon_pwchange('Passwort ändern'), 'account='.$acc['id'])."&#160;&#160;&#160;".internal_link('save', icon_delete("»{$local}@{$domain->fqdn}« löschen"), 'action=delete&account='.$acc['id']).'</td></tr>');
}

output('</table>');

addnew("new_account", "Neues Jabber-Konto anlegen");
addnew("new_domain", "Eigene Domain für Jabber freischalten");

$obsolete_domains = domains_without_accounts();

if (count($obsolete_domains) > 0) {
    output('<h4>Unbenutzte Jabber-Domains</h4>');
    output('<p>Folgende Domains sind für die Nutzung im Jabber-Server eingerichtet, werden aber momentan nicht genutzt (kein Jabber-Account vorhanden).</p><ul>');
    foreach ($obsolete_domains as $d) {
        output('<li><strong>'.$d->fqdn.'</strong> '.internal_link('save', icon_delete('Domain aus dem Jabber-Server entfernen'), 'action=deldomain&domain='.$d->id).'</li>');
    }
    output('</ul>');
}

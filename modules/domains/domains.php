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

require_role(array(ROLE_SYSTEMUSER, ROLE_CUSTOMER));

if (have_role(ROLE_CUSTOMER)) {
    $user_domains = get_domain_list($_SESSION['customerinfo']['customerno']);
} else {
    $user_domains = get_domain_list($_SESSION['userinfo']['customerno'], $_SESSION['userinfo']['uid']);
}

// Session-Variablen aufräumen
unset($_SESSION['domains_detail_domainname']);
unset($_SESSION['domains_detail_owner']);
unset($_SESSION['domains_detail_admin_c']);
unset($_SESSION['domains_detail_detach']);
unset($_SESSION['domains_domainreg_owner']);
unset($_SESSION['domains_domainreg_admin_c']);
unset($_SESSION['domains_domainreg_detach']);
unset($_SESSION['domains_domainreg_domainname']);

title("Domains");

output('<p>In Ihrem Account werden die folgenden Domains verwaltet:</p>');

output('<div class="domain-list">');
foreach ($user_domains as $domain) {
    $status = 'regular';
    $locked = '';
    $mailserver_lock = '';
    if ($domain->mail != 'none' && $domain->mailserver_lock == 1) {
        $locked = 'locked';
        $mailserver_lock = '<br><strong>Mail-Verarbeitung eingeschränkt!</strong>'.footnote('Diese Domain ist extern registriert und wurde noch nicht bestätigt. Momentan ist daher der Mail-Empfang auf dieser Domain nicht möglich.');
    }
    $regdate = $domain->reg_date;
    if ($domain->status == 'prereg') {
        $status = 'prereg';
        $regdate = '<em>Registrierung nicht abgeschlossen</em>';
    } elseif ($domain->status == 'transferfailed') {
        $status = 'pretransfer';
        $regdate = '<em>Umzug gescheitert</em>';
    } elseif ($domain->status == 'pretransfer') {
        $status = 'pretransfer';
        $regdate = '<em>Umzug bevorstehend</em>';
    } elseif ($domain->provider != 'terions') {
        $status = 'external';
        $regdate = '<em>Extern registriert</em>';
    } elseif ($domain->reg_date == null) {
        $status = 'pretransfer';
        $regdate = '<em>Umzug bevorstehend</em>';
    } else {
        $status = 'regular';
        $regdate = 'Registriert seit '.$regdate;
    }
    if ($domain->cancel_date) {
        $status = 'cancel-scheduled';
        $regdate .= '<br />Gekündigt zum '.$domain->cancel_date;
    }
    if ($domain->cancel_date && $domain->cancel_date < date('Y-m-d')) {
        $status = 'cancelled';
    }

    $features = array();
    if ($domain->dns == 1) {
        if (dns_in_use($domain->id)) {
            $features[] = 'DNS';
        }
        //if ($domain->autodns == 1)
    //  $features[] = 'AutoDNS';
    }
    $mailman = mailman_subdomains($domain->id);
    if (mail_in_use($domain->id)) {
        $features[] = 'Mail';
    }
    if ($mailman) {
        $features[] = 'Mailinglisten';
    }
    if (web_in_use($domain->id)) {
        $features[] = 'Web';
    }
    if ($domain->jabber == 1) {
        $features[] = 'Jabber';
    }

    $features = implode(', ', $features);
    if (! $features) {
        $features = '<em>unbenutzt</em>';
    }
    $punycode = $domain->punycode;
    if ($domain->is_idn) {
        $punycode = "<br/><span class=\"punycode\">($punycode)</span>";
    } else {
        $punycode = '';
    }
    $domainname = "{$domain->fqdn}{$punycode}";
    if (have_role(ROLE_CUSTOMER)) {
        $domainname = internal_link('detail', $domainname, 'id='.$domain->id);
    }
    output("  <div class=\"domain-item {$status} {$locked}\"><p class=\"domainname\">{$domainname}</p><p class=\"regdate\">{$regdate}</p><p class=\"domain-usage\">Verwendung: {$features}{$mailserver_lock}</p></div>\n");
}
output('</div>');
output("<br />");

if (have_role(ROLE_CUSTOMER) && config('http.net-apikey')) {
    addnew('adddomain', 'Neue Domain bestellen oder externe Domain hinzufügen');
}

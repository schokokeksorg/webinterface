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

$section = 'dns_dyndns';
$dyndns = get_dyndns_account($_REQUEST['id']);

title("Hostnames für DynDNS-Account " . filter_output_html($dyndns['handle']));

$available_domains = [];

$domains = get_domain_list($_SESSION['customerinfo']['customerno'], $_SESSION['userinfo']['uid']);
foreach ($domains as $d) {
    if ($d->dns) {
        $available_domains[$d->id] = $d->fqdn;
    }
}


$records = get_dyndns_records($dyndns['id']);

if ($records) {
    $output .= '<h4>Folgende DNS-records sind mit diesem DynDNS-Account verknüpft:</h4>
<ul>
';
    foreach ($records as $record) {
        $type = strtoupper($record['type']) . ' / ' . ($record['type'] == 'a' ? 'IPv4' : 'IPv6');
        $output .= '  <li>' . $record['fqdn'] . ' (' . $type . ') ' . internal_link('dyndns_hostname_delete', icon_delete(), 'id=' . $record['id']) . '</li>';
    }
    $output .= '</ul>';
}


output('<h4>Neuen Hostname festlegen</h4>');

$form = '<p><label for="hostname">Neuer Hostname: </label> <input type="text" name="hostname" id="hostname" value="' . filter_output_html($dyndns['handle']) . '" />&#160;.&#160;' . html_select('domain', $available_domains) . ' </p>
<p>Typ: <select name="type"><option value="a" selected="selected">A / IPv4</option><option value="aaaa">AAAA / IPv6</option></select></p>
<p><input type="submit" value="Speichern"/></p>';


output(html_form('dyndns_hostname_add', 'dyndns_hostname_add', 'id=' . $dyndns['id'], $form));

output('<p>' . internal_link('dyndns', 'Zurück zur Übersicht') . "</p>");

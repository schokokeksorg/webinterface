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
require_once('inc/icons.php');

require_role(ROLE_SYSTEMUSER);

require_once('dnsinclude.php');

$section = 'dns_dyndns';
$dyndns = get_dyndns_account($_REQUEST['id']);

title("Hostnames für DynDNS-Account ".filter_input_general($dyndns['handle']));

$available_domains = array();

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
  foreach ($records AS $record) {
    $output .= '  <li>'.$record['fqdn'].' '.internal_link('dyndns_hostname_delete', icon_delete(), 'id='.$record['id']).'</li>';
  }
  $output .= '</ul>';
}


output('<h4>Neuen Hostname festlegen</h4>');

$form = '<p><label for="hostname">Neuer Hostname: </label></td><td><input type="text" name="hostname" id="hostname" value="'.$dyndns['handle'].'" />&#160;.&#160;'.html_select('domain', $available_domains).' <input type="submit" value="Speichern"/></p>';


output(html_form('dyndns_hostname_add', 'dyndns_hostname_add', 'id='.$dyndns['id'], $form));

output('<p>'.internal_link('dyndns', 'Zurück zur Übersicht')."</p>");



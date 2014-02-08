<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2014 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/base.php');
require_once('inc/security.php');

require_role(ROLE_SYSTEMUSER);

require_once('dnsinclude.php');


$domains = get_domain_list($_SESSION['customerinfo']['customerno'], $_SESSION['userinfo']['uid']);

title('DNS-Records');
output('<p>Hier sehen Sie eine Übersicht über die angelegten DNS-records zu Ihren Domains.</p>');

//$output .= '<table><tr><th>Hostname</th><th>Typ</th><th>IP-Adresse/Inhalt</th><th>TTL</th><th>&#160;</th></tr>
//';

$output .=  '<table><tr><th>Domainname</th><th>Manuelle records</th><th>Automatische records</th></tr>';

DEBUG($domains);

foreach($domains AS $dom)
{
  if ($dom->dns == 0)
    continue;
  $records = get_domain_records($dom->id);

  $autorec = ($dom->autodns == 1 ? 'Ja' : 'Nein');
  $output .= '<tr><td>'.internal_link('dns_domain', $dom->fqdn, "dom={$dom->id}").'</td><td>'.count($records).'</td><td>'.$autorec.'</td></tr>';

/*  if ($records) 
  {
    #$output .= '<h4>'.$dom->fqdn.'</h4>';
    #$output .= '<table><tr><th>Hostname</th><th>Typ</th><th>IP-Adresse/Inhalt</th><th>TTL</th><th>&#160;</th></tr>
    #';
    foreach ($records AS $rec)
    {
      $data = ( $rec['ip'] ? $rec['ip'] : $rec['data'] );
      if ($rec['dyndns'])
        $data = '<em>DynDNS #'.$rec['dyndns'].'</em>';
      $output .= "<tr><td>".internal_link('dns_edit', $rec['fqdn'], "id={$rec['id']}")."</td><td>".strtoupper($rec['type'])."</td><td>$data</td><td>{$rec['ttl']} Sek.</td><td>".internal_link('save', '<img src="'.$prefix.'images/delete.png" width="16" height="16" alt="löschen" title="Account löschen" />', "id={$rec['id']}&type=dns&action=delete")."</td></tr>\n";
    }
    #$output .= '</table><br />';

  }*/
}

$output .= '</table><br />';

?>

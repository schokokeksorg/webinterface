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
require_once('inc/icons.php');
require_once('inc/security.php');

require_role(ROLE_SYSTEMUSER);

require_once('dnsinclude.php');


$domains = get_domain_list($_SESSION['customerinfo']['customerno'], $_SESSION['userinfo']['uid']);

title('DNS-Records');
output('<p>Hier sehen Sie eine Übersicht über die angelegten DNS-records zu Ihren Domains.</p>');

//$output .= '<table><tr><th>Hostname</th><th>Typ</th><th>IP-Adresse/Inhalt</th><th>TTL</th><th>&#160;</th></tr>
//';

$output .=  '<table><tr><th>Domainname</th><th>Manuelle records</th><th>Automatische records</th><th>Status</th></tr>';

DEBUG($domains);

$external_domains = false;
$unused_dns = false;
foreach ($domains as $dom) {
    if ($dom->provider != 'terions') {
        $external_domains = true;
    }
    $style="";
    if ($dom->dns == 0) {
        if (strstr($dom->domainname, '.')) {
            $output .= '<tr style="color: #999;"><td>'.$dom->fqdn.'</td><td>---</td><td>---</td><td>Subdomain ohne eigene DNS-Zone</td></tr>';
        } else {
            $output .= '<tr style="color: #999;"><td>'.$dom->fqdn.'</td><td>---</td><td>---</td><td>'.icon_disabled('DNS-Server ausgeschaltet').' Es wird ein externer DNS-Server benutzt<br />'.internal_link('save', icon_add().' Lokalen DNS-Server aktivieren', "dom={$dom->id}&dns=1").'</td></tr>';
        }
        continue;
    }
    $records = get_domain_records($dom->id);

    $autorec = ($dom->autodns == 1 ? 'Ja' : 'Nein');
    if ($dom->provider != 'terions' || $dom->billing != 'regular' || $dom->registrierungsdatum == null || $dom->kuendigungsdatum != null) {
        $state = check_dns($dom->domainname, $dom->tld);
        if ($state !== true) {
            $current = 'Momentaner DNS-Server (u.A.): '.$state;
            if ($state == 'NXDOMAIN') {
                $current = 'Diese Domain ist aktuell nicht registriert.';
            }
            if (substr_compare($state, config('masterdomain'), -strlen(config('masterdomain')), strlen(config('masterdomain'))) === 0) {
                $output .= '<tr><td>'.internal_link('dns_domain', $dom->fqdn, "dom={$dom->id}").'</td><td>'.count($records).'</td><td>'.$autorec.'</td><td>'.icon_enabled('DNS-Server aktiv').'<br />'.icon_warning().'Es werden veraltete DNS-Server benutzt<br />'.$current.'</td></tr>';
                continue;
            } else {
                $output .= '<tr><td>'.internal_link('dns_domain', $dom->fqdn, "dom={$dom->id}").'</td><td>'.count($records).'</td><td>'.$autorec.'</td><td>'.icon_enabled('DNS-Server aktiv').'<br />'.icon_warning().' Lokaler DNS-Server eingeschaltet aber nicht genutzt<br />'.$current.'<br />'.internal_link('save', icon_delete().' Lokalen DNS-Server abschalten', "dom={$dom->id}&dns=0").'</td></tr>';
                $unused_dns = true;
                continue;
            }
        }
    }
    $output .= '<tr><td>'.internal_link('dns_domain', $dom->fqdn, "dom={$dom->id}").'</td><td>'.count($records).'</td><td>'.$autorec.'</td><td>'.icon_enabled('DNS-Server aktiv').'</td></tr>';

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

if ($external_domains) {
    $own_ns = own_ns();
    asort($own_ns);
    $output.='<h4>Hinweis zu extern registrierten Domains</h4>
<p>Wenn Sie Ihre Domains bei einem anderen Provider registrieren und dennoch unsere DNS-Server nutzen möchten, dann stellen Sie bitte sicher, dass der DNS-Server oben eingeschaltet ist und stellen Sie dann folgende DNS-Server ein:<p>
<ul>';
    foreach ($own_ns as $ns) {
        $output.='<li>'.$ns.'</li>';
    }
    $output.='</ul>';
}
if ($unused_dns) {
    $output.='<h4>Wichtiger Hinweis</h4>
<p>In der obigen Liste befinden sich Domains, bei denen unser DNS-Server aktiviert ist aber die Domain momentan auf einen anderen DNS-Server eingerichtet ist. <strong>Dies ist normal bei bevorstehenden Domain-Transfers zu uns</strong>, sollte aber nicht dauerhaft so bleiben.<p>
<p>Wenn Sie weiterhin einen externen DNS-Server benutzen möchten, dann schalten Sie bitte unseren DNS-Server für diese Domain aus, damit es nicht zu Fehlfunktionen kommt.</p>
<p>Im Zweifel sprechen Sie bitte unseren Support an.</p>';
}

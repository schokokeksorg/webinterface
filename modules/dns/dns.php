<?php

require_once('inc/base.php');
require_once('inc/security.php');

require_role(ROLE_SYSTEMUSER);
require_role(ROLE_CUSTOMER);

require_once('dnsinclude.php');


$domains = get_domain_list($_SESSION['customerinfo']['customerno'], $_SESSION['userinfo']['uid']);

$output .= '<h3>DNS-Records</h3>
<p>Hier sehen Sie eine Übersicht über die angelegten DNS-records zu Ihren Domains.</p>';

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

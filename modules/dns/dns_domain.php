<?php

require_once('inc/base.php');
require_once('inc/security.php');

require_once('class/domain.php');

require_role(ROLE_CUSTOMER);

require_once('dnsinclude.php');

$section = 'dns_dns';

$domain = new Domain((int) $_REQUEST['dom']);

DEBUG($domain);

output('<h3>DNS-Records für <em>'.filter_input_general($domain->fqdn).'</em></h3>');

$records = get_domain_records($domain->id);
$auto_records = get_domain_auto_records($domain->fqdn);

output('<table><tr><th>Hostname</th><th>Typ</th><th>IP-Adresse/Inhalt</th><th>TTL</th><th>&#160;</th></tr>
');
foreach ($records AS $rec)
{
  $data = ( $rec['ip'] ? $rec['ip'] : $rec['data'] );
  if ($rec['dyndns'])
  {
    $dyndns = get_dyndns_account($rec['dyndns']);
    $data = internal_link('dyndns_edit', '<em>DynDNS #'.$rec['dyndns'].' ('.$dyndns['handle'].')</em>', 'id='.$rec['dyndns']);
  }
  if ($rec['type'] == 'mx')
  {
    $data .= ' ('.$rec['spec'].')';
  }
  $ttl = ($rec['ttl'] ? $rec['ttl'] : 3600);
  output("<tr><td>".internal_link('dns_record_edit', $rec['fqdn'], "id={$rec['id']}")."</td><td>".strtoupper($rec['type'])."</td><td>$data</td><td>{$ttl} Sek.</td><td>".internal_link('dns_record_save', '<img src="'.$prefix.'images/delete.png" width="16" height="16" alt="löschen" title="Record löschen" />', "id={$rec['id']}&action=delete")."</td></tr>\n");
}  
foreach ($auto_records AS $rec)
{
  $data = ( $rec['ip'] ? $rec['ip'] : $rec['data'] );
  $ttl = ($rec['ttl'] ? $rec['ttl'] : 3600);
  output("<tr><td><em>{$rec['fqdn']}</em></td><td>".strtoupper($rec['type'])."</td><td>$data</td><td>{$ttl} Sek.</td><td>&#160;</td></tr>\n");
  
}


output('</table>');

if ($domain->autodns)
  output('<p style="font-size: 80%;"><em>Kursive Hostnames bezeichnen automatisch erzeugte Records. Diese können nicht geändert werden.</em></p>');
else
  output('<p style="font-size: 80%;"><em>Für diese Domain wurde die Erzeugung automatischer Records deaktiviert.</em></p>');


output('<h4>Neuen DNS-Record anlegen</h4>
<p>Je nach dem, welchen Eintrags-Typ Sie anlegen möchten, werden im nächsten Schritt unterschiedliche Daten abgefragt. Bitte klicken Sie auf den Eintrags-Typ, den Sie anlegen möchten. Momentan werden noch nicht alle Eintrags-Typen über dieses System bereitgestellt. Hier nicht aufgeführte Eintragsarten können Sie beim Support beantragen.</p>

<ul>
<li>'.internal_link('dns_record_edit', 'DynDNS (Hostname für einen DynDNS-Account setzen)', 'id=new&type=dyndns&domain='.$domain->id).'</li>
<li>'.internal_link('dns_record_edit', 'A (normaler Hostname/normale Subdomain)', 'id=new&type=a&domain='.$domain->id).'</li>
<li>'.internal_link('dns_record_edit', 'MX (Posteingangsserver)', 'id=new&type=mx&domain='.$domain->id).'</li>
</ul>
<ul>
<li>'.internal_link('dns_record_edit', 'AAAA (IPv6-Adresse)', 'id=new&type=aaaa&domain='.$domain->id).'</li>
<li>'.internal_link('dns_record_edit', 'CNAME (Aliasnamen)', 'id=new&type=cname&domain='.$domain->id).'</li>
</ul>
');

?>

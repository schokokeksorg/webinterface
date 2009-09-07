<?php

require_once('inc/base.php');
require_once('inc/security.php');

require_once('class/domain.php');

require_role(ROLE_CUSTOMER);

require_once('dnsinclude.php');

$section = 'dns_dns';

$domain = new Domain((int) $_REQUEST['dom']);
$domain->ensure_customerdomain();

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
  $link = $rec['fqdn'];
  if (in_array($rec['type'], array('a', 'aaaa', 'mx', 'cname', 'ns', 'txt', 'spf'))) {
      $link = internal_link('dns_record_edit', $rec['fqdn'], "id={$rec['id']}");
  }
  output("<tr><td>{$link}</td><td>".strtoupper($rec['type'])."</td><td>$data</td><td>{$ttl} Sek.</td><td>".internal_link('dns_record_save', '<img src="'.$prefix.'images/delete.png" width="16" height="16" alt="löschen" title="Record löschen" />', "id={$rec['id']}&action=delete")."</td></tr>\n");
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
<li>'.internal_link('dns_record_edit', 'NS (Nameserver)', 'id=new&type=ns&domain='.$domain->id).'</li>
<li>'.internal_link('dns_record_edit', 'SPF (sender policy framework)', 'id=new&type=spf&domain='.$domain->id).'</li>
<li>'.internal_link('dns_record_edit', 'TXT', 'id=new&type=txt&domain='.$domain->id).'</li>
</ul>

<h4>Automatische DNS-Records</h4>
');

if ($domain->autodns)
{
  output("<p>Automatische Einträge können nicht geändert werden. Möchten Sie davon abweichende Records setzen, so können Sie hiermit alle automatischen Einträge in normale Einträge konvertieren und die Erzeugung neuer automatischer Einträge abschalten. Diese Einstellung betrifft nur diese Domain und kann jederzeit geändert werden.</p>
<p>".internal_link('dns_save', 'Automatisch erzeugte Einträge umwandeln', "type=autodns&action=disable&dom={$domain->id}")."</p>");
}
else
{
  output("<p>Sie verwealten Ihre DNS-Einträge selbst. Wenn Sie möchten, können Sie die DNS-Einträge auch automatisch anhand der angelegten Webserver-VHosts und anderer Einstellungen festlegen lassen. Diese Eintäge können Sie dann nicht direkt ändern. Ihre bestehenden Einträge bleiben unberührt und zusätzlich erhalten. Bitte löschen Sie dadurch entstehende Duplikate!</p>
<p>".internal_link('dns_save', 'Automatisch erzeugte Einträge aktivieren', "type=autodns&action=enable&dom={$domain->id}")."</p>");
}

?>

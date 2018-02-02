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

require_once('inc/icons.php');
require_once('inc/security.php');

require_once('class/domain.php');

require_role(ROLE_SYSTEMUSER);

require_once('dnsinclude.php');

$section = 'dns_dns';

$domain = new Domain((int) $_REQUEST['dom']);
$domain->ensure_userdomain();

DEBUG($domain);

title('DNS-Records für '.filter_input_general($domain->fqdn));
headline('DNS-Records für <em>'.filter_input_general($domain->fqdn).'</em>');

$records = get_domain_records($domain->id);
$auto_records = get_domain_auto_records($domain->fqdn);
$cname_on_domain = false;

output('<table><tr><th>Hostname</th><th>Typ</th><th>IP-Adresse/Inhalt</th><th>TTL</th><th>&#160;</th></tr>
');
foreach ($records AS $rec)
{
  $editable = true;
  $data = filter_input_general( $rec['ip'] ? $rec['ip'] : $rec['data'] );
  if ($rec['dyndns'])
  {
    if ($domain->fqdn == config('masterdomain'))
    { 
      $data = '<em>DynDNS #'.(int) $rec['dyndns'].'</em>';
      $editable = false;
    } else {
      $dyndns = get_dyndns_account($rec['dyndns']);
      if ($dyndns === NULL) {
        $data = '<em>DynDNS #'.(int) $rec['dyndns'].' (nicht Ihr Account)</em>';
      } else {
        $data = internal_link('dyndns_edit', '<em>DynDNS #'.(int) $rec['dyndns'].' ('.filter_input_general($dyndns['handle']).')</em>', 'id='.(int) $rec['dyndns']);
      }
    }
  }
  if ($rec['type'] == 'mx')
  {
    $data .= ' ('.(int) $rec['spec'].')';
  }
  if ($rec['type'] == 'sshfp')
  {
    $data = (int) $rec['spec'] . ' 1 ' . $data;
  }
  if ($rec['type'] == 'caa')
  {
    $data = $caa_properties[(int) $rec['spec']] . ' 0 "' . $data.'"';
  }
  $ttl = ($rec['ttl'] ? $rec['ttl'] : 3600);
  $link = $rec['fqdn'];
  if (!in_array($rec['type'], array('a', 'aaaa', 'mx', 'cname', 'ns', 'txt', 'spf', 'ptr', 'sshfp', 'caa'))) {
      $editable = false;
  }
  $delete = internal_link('dns_record_save', icon_delete('Record löschen'), "id={$rec['id']}&action=delete");
  if ($rec['type'] == 'ns' && ! $rec['hostname']) {
      $editable = false;
      $delete = '';
  }
  if ($editable) {
      $link = internal_link('dns_record_edit', $rec['fqdn'], "id={$rec['id']}");
  }
  output("<tr><td>{$link}</td><td>".strtoupper($rec['type'])."</td><td>".$data."</td><td>{$ttl} Sek.</td><td>".$delete."</td></tr>\n");
}  
foreach ($auto_records AS $rec)
{
  $data = filter_input_general( $rec['ip'] ? $rec['ip'] : $rec['data'] );
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
<li>'.internal_link('dns_record_edit', 'NS (Nameserver, NUR FÜR SUBDOMAINS!)', 'id=new&type=ns&domain='.$domain->id).'</li>
<li>'.internal_link('dns_record_edit', 'TXT', 'id=new&type=txt&domain='.$domain->id).'</li>
<li>'.internal_link('dns_record_edit', 'SSHFP', 'id=new&type=sshfp&domain='.$domain->id).'</li>
<li>'.internal_link('dns_record_edit', 'CAA', 'id=new&type=caa&domain='.$domain->id).'</li>
</ul>

<h4>Automatische DNS-Records</h4>
');

if ($domain->autodns)
{
  output("<p>Für extrem ungewöhnliche Konfigurationen können Sie die Erzeugung von automatischen DNS-Records unter dieser Domain komplett abschalten. Dies ist i.d.R. falsch und wird zu Fehlfunktion führen. Nutzen Sie diese Einstellung auf eigene Gefahr.</p>
  <p>Möchten Sie einzelne DNS-Einträge abweichend setzen, so legen Sie einfach oben den jeweils gewünschten Record an. Falls es gleichlautende automatische Einträge gibt, werden diese anschließend unterdrückt.</p>
  <p>Warten Sie nach Änderung dieser Einstellung eine Minute und laden Sie danach die Seite neu.</p>
<p>".internal_link('dns_save', 'Automatisch erzeugte Einträge umwandeln', "type=autodns&action=disable&dom={$domain->id}")."</p>");
}
else
{
  output("<p>Sie verwalten Ihre DNS-Einträge selbst. Wenn Sie möchten, können Sie die DNS-Einträge auch automatisch anhand der angelegten Webserver-VHosts und anderer Einstellungen festlegen lassen. Diese Eintäge können Sie dann nicht direkt ändern. Ihre bestehenden Einträge bleiben unberührt und zusätzlich erhalten. Bitte löschen Sie dadurch entstehende Duplikate!</p>
  <p>Warten Sie nach Änderung dieser Einstellung eine Minute und laden Sie danach die Seite neu.</p>
<p>".internal_link('dns_save', 'Automatisch erzeugte Einträge aktivieren', "type=autodns&action=enable&dom={$domain->id}")."</p>");
}

?>

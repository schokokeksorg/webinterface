<?php

require_once('inc/base.php');
require_once('inc/security.php');

require_once('class/domain.php');

require_role(ROLE_CUSTOMER);

require_once('dnsinclude.php');

$section = 'dns_dns';

$domain = new Domain((int) $_REQUEST['dom']);

DEBUG($domain);

$output .= '<h3>DNS-Records für <em>'.filter_input_general($domain->fqdn).'</em></h3>';

$records = get_domain_records($domain->id);
$auto_records = get_domain_auto_records($domain->fqdn);

$output .= '<table><tr><th>Hostname</th><th>Typ</th><th>IP-Adresse/Inhalt</th><th>TTL</th><th>&#160;</th></tr>
';
foreach ($records AS $rec)
{
  $data = ( $rec['ip'] ? $rec['ip'] : $rec['data'] );
  if ($rec['dyndns'])
    $data = internal_link('dyndns_edit', '<em>DynDNS #'.$rec['dyndns'].'</em>', 'id='.$rec['dyndns']);
  $ttl = ($rec['ttl'] ? $rec['ttl'] : 3600);
  $output .= "<tr><td>".internal_link('dns_record_edit', $rec['fqdn'], "id={$rec['id']}")."</td><td>".strtoupper($rec['type'])."</td><td>$data</td><td>{$ttl} Sek.</td><td>".internal_link('save', '<img src="'.$prefix.'images/delete.png" width="16" height="16" alt="löschen" title="Record löschen" />', "id={$rec['id']}&type=dns&action=delete")."</td></tr>\n";
}  
foreach ($auto_records AS $rec)
{
  $data = ( $rec['ip'] ? $rec['ip'] : $rec['data'] );
  $ttl = ($rec['ttl'] ? $rec['ttl'] : 3600);
  $output .= "<tr><td><em>{$rec['fqdn']}</td><td>".strtoupper($rec['type'])."</td><td>$data</td><td>{$ttl} Sek.</td><td>&#160;</td></tr>\n";
  
}


$output .= '</table>';

if ($domain->autodns)
  $output .= '<p style="font-size: 80%;"><em>Kursive Hostnames bezeichnen automatisch erzeugte Records. Diese können nicht geändert werden.</em></p>';
else
  $output .= '<p style="font-size: 80%;"><em>Für diese Domain wurde die Erzeugung automatischer Records deaktiviert.</em></p>';


$output .= html_form('dns_record_new', 'dns_record_edit', 'id=new&dom='.$domain->id, 
'<h4>Neuen DNS-Record anlegen</h4>
<p>
<label for="type">Typ:</label>&#160;'.html_select('type', array('a' => 'A', 'aaaa' => 'AAAA', 'mx' => 'MX', 'ns' => 'NS', 'spf' => 'SPF', 'txt' => 'TXT', 'cname' => 'CNAME', 'ptr' => 'PTR', 'srv' => 'SRV', 'raw' => 'RAW'), 'a').'
&#160;&#160;&#160;<input type="submit" value="Anlegen" />
</p>');

?>

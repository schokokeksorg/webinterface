<?php

require_once('inc/base.php');
require_once('inc/security.php');

require_once('class/domain.php');

require_role(ROLE_SYSTEMUSER);

require_once('dnsinclude.php');

$section = 'dns_dns';

$data = array();
$type = NULL;

$dyndns = false;
$dyndns_accounts = array();
foreach (get_dyndns_accounts() AS $t)
{
  $dyndns_accounts[$t['id']] = $t['handle'];
}

if (isset($_REQUEST['type']) && $_REQUEST['type'] == "dyndns")
{
  $_REQUEST['type'] = 'a';
  $dyndns = true;
}

$new = false;
if ($_REQUEST['id'] == 'new')
{
  $new = true;
  $data = blank_dns_record($_REQUEST['type']);
  $domain = new Domain((int) $_REQUEST['domain']);
  $domain->ensure_userdomain();
  $type = $_REQUEST['type'];
  if (! in_array($type, $valid_record_types))
    system_failure('Ungültiger Record-Typ!');
  $data['domain'] = $domain->id;
  if ($dyndns)
    $data['ttl'] = 120;
}

if (! $new)
{
  $data = get_dns_record($_REQUEST['id']);
  $type = $data['type'];
  $dyndns = isset($data['dyndns']);
  $domain = new Domain((int) $data['domain']);
  $domain->ensure_userdomain();
  if (! in_array($type, $valid_record_types))
    system_failure('Ungültiger Record-Typ!');
}


if ($new)
  output('<h3>DNS-Record erstellen</h3>');
else
  output('<h3>DNS-Record bearbeiten</h3>');

output('<p style="border: 2px solid red; padding: 1em; padding-left: 4em;"><img src="'.$prefix.'images/warning.png" style="margin-left: -3em; float: left;" /><strong>Bitte beachten Sie:</strong> Um Ihnen auch ungewöhniche Konstellationen zu ermöglichen, erlaubt dieses Webinterface sehr großzügige Eintragungen, die eventuell nicht plausibel sind oder vom DNS-Server gar nicht so verstanden werden können. Wir können sicherheitskritische Einträge herausfiltern, jedoch nicht logische Fehler automatisch erkennen. Im Fehlerfall wird meistens Ihre gesamte Domain vom DNS-Server ausgeschlossen, so lange sich Fehler in der Konfiguration befinden. Sollten Sie hier also fehlerhafte Eintragungen machen, kann dies die Erreichbarkeit der betreffenden Domain im Ganzen stören.</p>');


output('<p>Record-Typ: '.strtoupper($type).'</p>');

$submit = 'Speichern';
if ($new) 
  $submit = 'Anlegen';

$form = '';

if (! $dyndns && ($type == 'a' || $type == 'aaaa'))
{
  $form .= '
<tr><td><label for="ip">IP-Adresse:</label></td><td><input type="text" name="ip" id="ip" value="'.$data['ip'].'" /></td></tr>
';
}

if ($type == 'ns')
{
  $form .= '
<tr><td><label for="data">DNS-Server:</label></td><td><input type="text" name="data" id="data" value="'.$data['data'].'" /></td></tr>
';
}

if ($type == 'ptr' || $type == 'cname')
{
  $form .= '
<tr><td><label for="data">Ziel:</label></td><td><input type="text" name="data" id="data" value="'.$data['data'].'" /></td></tr>
';
}

if ($type == 'spf' || $type == 'txt')
{
  $form .= '
<tr><td><label for="data">Inhalt:</label></td><td><input type="text" name="data" id="data" value="'.$data['data'].'" /></td></tr>
';
}

if ($dyndns)
{
  $form .= '
<tr><td><label for="dyndns">DynDNS-Zugang:</label></td><td>'.html_select('dyndns', $dyndns_accounts, $data['dyndns']).'</td></tr>
';
}

if ($type == 'mx')
{
  $form .= '
<tr><td><label for="spec">Priorität:</label></td><td><input type="text" name="spec" id="spec" value="'.$data['spec'].'" /></td></tr>
<tr><td><label for="data">Posteingangsserver:</label></td><td><input type="text" name="data" id="data" value="'.$data['data'].'" /></td></tr>
';
}


output(html_form('dns_record_edit', 'dns_record_save', "type={$type}&domain={$domain->id}&id={$_REQUEST['id']}", '<table>
<tr><td><label for="hostname">Hostname:</label></td><td><input type="text" name="hostname" id="hostname" value="'.$data['hostname'].'" />&#160;<strong>.'.$domain->fqdn.'</strong></td></tr>
'.$form.'
<tr><td><label for="ttl">TTL:</label></td><td><input type="text" name="ttl" id="ttl" value="'.$data['ttl'].'" /></td></tr>
</table>
<p><input type="submit" value="'.$submit.'" /></p>
'));

?>

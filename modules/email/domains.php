<?php

require_once('inc/base.php');
require_once('inc/security.php');
require_role(ROLE_SYSTEMUSER);

require_once('vmail.php');

$settings = domainsettings();

$domains = $settings['domains'];
$subdomains = $settings['subdomains'];

DEBUG($settings);

output('<h3>E-Mail-Verwaltung</h3>
<p>Sie können bei '.config('company_name').' die E-Mails Ihrer Domains auf zwei unterschiedliche Arten empfangen.</p>
<ol><li>Sie können einfache E-Mail-Konten erstellen, die ankommende E-Mails speichern oder weiterleiten.</li>
<li>Sie können die manuelle Verwaltung wählen, bei der Sie passende .courier-Dateien für den Empfang und
manuelle POP3/IMAP-Konten für den Abruf erstellen können.</li></ol>
<p>Diese Wahlmöglichkeit haben Sie pro Domain bzw. Subdomain. Eine parallel Nutzung beider Verfahren ist nicht möglich.
Wenn Sie eine Domain auf Webinterface-Verwaltung einrichten, dann werden eventuell vorhandene .courier-Dateien nicht mehr 
beachtet. Subdomains können grundsätzlich nur durch Administratoren eingerichtet und verändert werden.</p>

<h4>Ihre Domains sind momentan wie folgt konfiguriert:</h4>

<table>
  <tr><th>Domainname</th><th>Einstellung</th><th></th></tr>
');

foreach ($domains AS $id => $dom) {
  $type = maildomain_type($dom['type']);
  $edit = html_form('vmail_domainchange', 'domainchange', '', html_select('type', array('virtual' => 'Webinterface-Verwaltung', 'auto' => '.courier-Dateien', 'none' => 'keine E-Mails empfangen'), $dom['type']).' <input type="hidden" name="id" value="'.$id.'" /><input type="submit" value="ändern" />');
  if ($dom['type'] == 'manual')
    $edit = 'Kann nur von Admins geändert werden';
  if (domain_has_vmail_accounts($id))
    $edit = 'Keine Änderung möglich, so lange noch '.internal_link("vmail", "E-Mail-Konten").' für diese Domain eingerichtet sind.';
  output("<tr><td>{$dom['name']}</td><td>{$type}</td><td>{$edit}</td></tr>\n");
  if (array_key_exists($id, $subdomains)) {
    foreach ($subdomains[$id] AS $subdom) {
      $type = maildomain_type($subdom['type']);
      output("<tr><td>{$subdom['name']}.{$dom['name']}</td><td>{$type}</td><td>Subdomains können nur von Admins geändert werden!</td></tr>\n");
    }
  }
}
output('</table>
<br />');

output('<p><strong>Sicherheitshinweis:</strong> Während der Umstellung der Empfangsart ist Ihre Domain eventuell für einige Minuten in einem undefinierten Zustand. In dieser Zeit kann es passieren, dass E-Mails nicht korrekt zugestellt oder sogar ganz zurückgewiesen werden. Sie sollten diese Einstellungen daher nicht mehr ändern, wenn die Domain aktiv für den E-Mail-Verkehr benutzt wird.</p>
');



?>

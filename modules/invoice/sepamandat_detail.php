<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('invoice.php');

require_role(ROLE_CUSTOMER);
$section = 'invoice_current';

title('Daten Ihres Lastschrift-Mandats');

$ref = $_REQUEST['ref'];
$mandate = get_sepamandate();
$m = null;

foreach ($mandate as $man) {
    if ($man['mandatsreferenz'] == $ref) {
        $m = $man;
    }
}
if (! $m) {
    system_failure('Konnte das Mandat nicht finden.');
}

if ($m['medium'] == 'legacy') {
    output('<p>Sie hatten uns vor längerer Zeit eine Einzugsermächtigung ausgesprochen. Wir haben diese selbstständig in das nachstehende SEPA-Mandat überführt.</p>');
} else {
    $medium = 'über unser Webinterface';
    switch ($m['medium']) {
    case 'email':
      $medium = 'per E-Mail';
      break;
    case 'fax':
      $medium = 'per Fax';
      break;
    case 'post':
      $medium = 'per Post';
      break;
  }
    output('<p>Wir haben das nachstehende Mandat am '.$m['erteilt'].' '.$medium.' entgegen genommen.</p>');
}
output('<h3>Stammdaten</h3>
<dl>
<dt>Mandatsreferenz</dt><dd>'.$m['mandatsreferenz'].'</dd>
<dt>Unsere Gläubiger-ID<dt><dd>'.$m['glaeubiger_id'].'</dd>
</dl>');

output('<h3>Gültigkeit</h3>');

$gueltigkeit = 'ab '.$m['gueltig_ab'];
if ($m['gueltig_ab'] < date('Y-m-d')) {
    $gueltigkeit = 'seit '.$m['gueltig_ab'];
}
if ($m['gueltig_bis']) {
    $gueltigkeit = 'von '.$m['gueltig_ab'].' bis '.$m['gueltig_bis'];
}
if ($m['gueltig_ab'] <= date('Y-m-d') && ($m['gueltig_bis'] == null || $m['gueltig_bis'] >= date('Y-m-d'))) {
    output('<p>Das Mandat ist momentan gültig ('.$gueltigkeit.').</p>');
} elseif ($m['gueltig_ab'] > date('Y-m-d')) {
    output('<p>Das Mandat ist noch nicht gültig ('.$gueltigkeit.').</p>');
} else {
    output('<p>Das Mandat ist erloschen ('.$gueltigkeit.').</p>');
}

$lastschriften = get_lastschriften($m['mandatsreferenz']);

if (! $lastschriften) {
    output('<p>Es wurden bisher keine Abbuchungen mit Bezug auf dieses Mandat durchgeführt.</p>');
} else {
    output('<p>Dieses Mandat wurde bisher für folgende Abbuchungen in Anspruch genommen:</p>
<ul>');
    foreach ($lastschriften as $l) {
        $status = '';
        if ($l['status'] == 'pending') {
            $status = '<span style="color: red; font-weight: bold;">Vorgemerkt:</span> ';
        }
        if ($l['status'] == 'rejected') {
            $status = '<span style="color: red; font-weight: bold;">Zurückgewiesen:</span> ';
        }
        output('<li>'.$status.'Rechnung #'.$l['rechnungsnummer'].' vom '.$l['rechnungsdatum'].' über <strong>'.str_replace('.', ',', sprintf('%.2f', $l['betrag'])).' €</strong>, Buchungsdatum '.$l['buchungsdatum'].'</li>');
    }
    output('</ul>');
}


output('<h3>Kontodaten</h3>
<dl>
<dt>Kontoinhaber</dt><dd>'.$m['kontoinhaber'].'</dd>
<dt>Adresse des Kontoinhabers</dt><dd>'.nl2br($m['adresse']).'</dd>
<dt>IBAN</dt><dd>'.$m['iban'].'</dd>
<dt>Name der Bank</dt><dd>'.$m['bankname'].'</dd>
<dt>BIC</dt><dd>'.$m['bic'].'</dd>
</dl>');


output('<p>'.internal_link('current', 'Zurück').'</p>');

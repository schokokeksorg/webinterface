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

require_once('session/start.php');
require_once('inc/icons.php');
require_once('inc/jquery.php');
javascript('more_storage.js');

require_once('invoice.php');

require_role(ROLE_CUSTOMER);

$_SESSION['more_storage_section'] = 'invoice_current';
if (isset($_GET['section'])) {
  $_SESSION['more_storage_section'] = $_GET['section'];
}
$section = $_SESSION['more_storage_section'];

title('Zusätzlichen Speicherplatz buchen');

$upcoming = upcoming_items();
$hosting = NULL;
foreach ($upcoming as $item) {
  if ($item['quelle'] == 'hosting') {
    $hosting = $item;
    break;
  }
}
if (!$hosting) {
  system_failure("Die Abrechnung für das normale Hosting konnte nicht ermittelt werden. Daher können Sie diese Funktion leider nicht benutzen. Wenden Sie sich bitte an die Administratoren um mehr Speicherplatz zu bestellen.");
}
if ($hosting['brutto'] == 0) {
  system_failure("Ihr Konto wird mit Nettopreisen abgerechnet. Bitte wenden Sie sich an den Support.");
}

$customerquota = get_customerquota();

$count = 1024;
if (isset($_POST['count']) && (int) $_POST['count'] > 0) {
  $count = (int) $_POST['count'];
}

output("<p>Ihr aktuell zugeteilter Speicherplatz (ggf. inklusive Mitbenutzer) beträgt <strong>${customerquota} MB</strong>. Sie können weiteren Speicherplatz hinzubuchen.</p>");

$checked = '';
if ($count == 1024) {
  $checked = 'checked="checked" ';
}
$form = '<p class="buttonset"><input '.$checked.'type="radio" name="count" value="1024" id="count-1" /><label for="count-1">Zusätzlich <strong>1 GB</strong>, insgesamt also '.($customerquota+1024).' MB</label>';
if ($count == 2048) {
  $checked = 'checked="checked" ';
} else {
  $checked = '';
}
$form .= '<input '.$checked.'type="radio" name="count" value="2048" id="count-2" /><label for="count-2">Zusätzlich <strong>2 GB</strong>, insgesamt also '.($customerquota+2048).' MB</label>';
if ($count == 5120) {
  $checked = 'checked="checked" ';
} else {
  $checked = '';
}
$form .= '<input '.$checked.'type="radio" name="count" value="5120" id="count-5" /><label for="count-5">Zusätzlich <strong>5 GB</strong>, insgesamt also '.($customerquota+5120).' MB</label>';

$form .= '<input type="submit" value="Wählen" /></p>';
output(html_form("more_storage_selection", "more_storage", "", $form));

$new_item = $hosting;
unset($new_item['quelle']);
unset($new_item['id']);
$gb = $count/1024;
$new_item['beschreibung'] = 'Erweiterung Speicherplatz um '.$gb.' GB (Auftrag vom '.date('d.m.Y').')';
$new_item['betrag'] = $gb;

$startdate = $new_item['startdatum'];
$diff = date_diff(date_create("now"), date_create($startdate), true);
DEBUG('date_diff(now, '.$startdate.') => '.$diff->format('%y Year %m Month %d Day'));
$months = $diff->format("%m");

$stub_startdate = date_create($startdate);
date_sub($stub_startdate, date_interval_create_from_date_string($months.' months'));
$stub_enddate = date_create($startdate);
date_sub($stub_enddate, date_interval_create_from_date_string('1 day'));

$stub = $new_item;
$stub['startdatum'] = date_format($stub_startdate, 'Y-m-d');
$stub['enddatum'] = date_format($stub_enddate, 'Y-m-d');
$stub['anzahl'] = $months;
$stub['monate'] = $months;

$items = array();
$items[] = $stub;
$items[] = $new_item;

output('<p>Die Abrechnung erfolgt mit Ihrer nächsten turnusgemäßen Abrechnung und wird mit folgenden Rechnungsposten vorgemerkt.</p>');
output('<table><tr><th>Anzahl</th><th>Beschreibung</th><th>Zeitraum</th><th>Einzelpreis</th><th>Gesamtbetrag</th></tr>');
$counter = 0;
foreach($items AS $item)
{
  $counter++;
  if ($counter == 1 && ($item['anzahl'] > 0)) {
    output("<tr><td colspan=\"5\" style=\"border: none;\"><em>Einmaliger Posten:</em></td></tr>");
  }
  elseif ($counter == 2) {
    output("<tr><td colspan=\"5\" style=\"border: none;\"><em>Künftiger regelmäßiger Posten:</em></td></tr>");
  }
  if ($item['anzahl'] == 0) {
    continue;
  }
	$desc = $item['startdatum'];
	if ($item['enddatum'] != NULL)
		$desc = $item['startdatum'].' - '.$item['enddatum'];
	$epreis = $item['betrag'];
	if ($item['brutto'] == 0)
		$epreis = $epreis * (1 + ($item['mwst'] / 100));
	$gesamt = round($epreis * $item['anzahl'], 2);
	$epreis = round($epreis, 2);
  $einheit = ($item['einheit'] ? $item['einheit'] : '');
	output("<tr><td>{$item['anzahl']} {$einheit}</td>");
  output("<td>{$item['beschreibung']}</td><td>{$desc}</td>");
	output("<td>{$epreis} €</td><td>{$gesamt} €</td></tr>\n");
}
output('</table>');

output('<p>Wir behalten uns vor, diese Rechnungsposten mit anderen sinngleichen Posten zusammen zu führen.</p>');

$handle = random_string(10);

$_SESSION['more_storage_handle'] = $handle;
$_SESSION['more_storage_items'] = $items;
$_SESSION['more_storage_count'] = $count;

$form = '';

if (have_module('systemuser')) {
  include('modules/systemuser/include/useraccounts.php');
  $useraccounts = list_useraccounts();

  if (count($useraccounts) == 1) {
    $form .= '<input type="hidden" name="more_storage_user" value="'.$useraccounts[0]['uid'].'" />';
  } else {
    $choices = array('' => 'Nicht zuweisen');
    foreach ($useraccounts as $u) {
      $choices[$u['uid']] = "Benutzer ${u['username']} vergrößern";
    }
    $form .= '<p>Wie soll der zusätzliche Speicherplatz verwendet werden?</p><p>'.html_select('more_storage_user', $choices, $_SESSION['userinfo']['uid']).'</p>';
  }
}

$form .= '<p>
<input type="hidden" name="more_storage_handle" value="'.$handle.'" />
<input type="submit" value="Jetzt zahlungspflichtig bestellen" /><p>';


output(html_form("more_storage", "more_storage_save", "action=more_storage", $form));

?>

<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('contacts.php');
require_once('inc/debug.php');
require_once('inc/jquery.php');
javascript();

require_once('session/start.php');


require_role([ROLE_CUSTOMER]);
$section = 'contacts_list';

$new = false;
if ($_REQUEST['id'] == 'new') {
    title("Adresse anlegen");
    $new = true;
} else {
    title("Adresse bearbeiten");
}

$c = new_contact();
if (! $new) {
    $c = get_contact($_REQUEST['id']);
} elseif (isset($_REQUEST['copy'])) {
    $c = get_contact($_REQUEST['copy']);
    $c['nic_handle'] = null;
}
$domains = domainlist_by_contact($c);

$readonly = '';
// Wenn das Handle beim NIC angemeldet ist, kann man Name und Land nicht mehr ändern
if ($c['nic_handle'] != null) {
    $readonly = ' disabled="disabled" ';
    output('<p>Da diese Adresse als möglicher Domaininhaber bei der Domain-Regristry angemeldet ist, können Name/Firmenname und Land nicht mehr geändert werden. Legen Sie ggf. eine neue Adresse an und ändern Sie den Domain-Inhaber entsprechend.</p>');
}
if (isset($_REQUEST['domainholder']) && $_REQUEST['domainholder'] == 1) {
    output('<p>Für einen Domaininhaber muss mindestens Name, vollständige Adresse, E-Mail-Adresse und Telefonnummer angegeben werden.</p>');
}
$odd = false;
$html = '<table>';
$buttons = '<span class="buttonset" id="buttonset-salutation">
         <input type="radio" name="salutation" id="salutation-firma" value="Firma" '.($c['salutation'] === null ? 'checked="checked"' : '').'/>
         <label for="salutation-firma">Neutral</label>
         <input type="radio" name="salutation" id="salutation-herr" value="Herr" '.($c['salutation'] === 'Herr' ? 'checked="checked"' : '').'/>
         <label for="salutation-herr">Herr</label>
         <input type="radio" name="salutation" id="salutation-frau" value="Frau" '.($c['salutation'] === 'Frau' ? 'checked="checked"' : '').'/>
         <label for="salutation-frau">Frau</label>';
$html .= '    <tr class="'.($odd == true ? 'odd' : 'even').'"><td>Bevorzugte Anrede:</td><td>'.$buttons.'</td></tr>';
$html .= '    <tr class="'.($odd == true ? 'odd' : 'even').'"><td>Firmenname:</td><td><input type="text" name="firma" id="firma" value="'.filter_output_html($c['company']).'" '.$readonly.' /></td></tr>';
$odd = !$odd;
$html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td>'.($c['company'] ? 'Ansprechpartner' : 'Name').':</td><td><input type="text" name="name" id="name" value="'.filter_output_html($c['name']).'" '.$readonly.' /></td></tr>';
$odd = !$odd;
$html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="adresse">Adresse:</label></td><td><textarea rows="3" name="adresse" id="adresse">'.filter_output_html($c['address']).'</textarea></td></tr>';
$odd = !$odd;
$html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="plz">Land / PLZ:</label></td><td><input size="2" type="text" name="land" id="land" value="'.filter_output_html($c['country']).'" '.$readonly.' />-</strong><input type="text" name="plz" id="plz" value="'.filter_output_html($c['zip']).'"></td></tr>';
$odd = !$odd;
$html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="ort">Ort:</label></td><td><input type="text" name="ort" id="ort" value="'.filter_output_html($c['city']).'"></td></tr>';
$odd = !$odd;


$html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="email">E-Mail-Adresse:</label></td><td><input type="text" name="email" id="email" value="'.filter_output_html($c['email']).'"></td></tr>';
$odd = !$odd;

$html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="telefon">Telefonnummer:</label></td><td><input type="text" name="telefon" id="telefon" value="'.filter_output_html($c['phone']).'"><span id="telefon_feedback"></span></td></tr>';
$odd = !$odd;
$html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="telefon">Mobil:</label></td><td><input type="text" name="mobile" id="mobile" value="'.filter_output_html($c['mobile']).'"><span id="mobile_feedback"></span></td></tr>';
$odd = !$odd;
$html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="telefon">Telefax:</label></td><td><input type="text" name="telefax" id="telefax" value="'.filter_output_html($c['fax']).'"><span id="telefax_feedback"></span></td></tr>';
$odd = !$odd;

if ($domains) {
    $html .= '<tr class="odd" id="designated-row"><td><label for="designated"><strong>Inhaberwechsel:</strong></label></td><td><input type="checkbox" name="designated" id="designated" value="yes"><label for="designated">Die Änderung der E-Mail-Adresse bewirkt rechtlich einen Inhaberwechsel. Ich bestätige, dass mir eine explizite Zustimmung des alten und neuen Inhabers für diese Änderung vorliegt. Eine Speicherung der Änderungen ist nur mit dieser Zustimmung möglich.</label></td></tr>';
}

  $buttons = '<span class="buttonset" id="buttonset-usepgp">
         <input type="radio" name="usepgp" id="usepgp-yes" value="yes" '.($c['pgp_id'] ? 'checked="checked"' : '').'/>
         <label for="usepgp-yes">PGP verwenden</label>
         <input type="radio" name="usepgp" id="usepgp-no" value="no" '.($c['pgp_id'] ? '' : 'checked="checked"').'/>
         <label for="usepgp-no">kein PGP</label>';
 $html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="buttonset-usepgp">PGP-Verschlüsselung:</label></td><td>'.$buttons.'</td></tr>';
 $html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="pgpid">PGP-Key-ID:</label></td><td><input type="text" name="pgpid" id="pgpid" value="'.filter_output_html($c['pgp_id']).'" size="40"><button id="searchpgp" type="button">Auf Keyserver suchen</button><span id="pgpid_feedback"></span></td></tr>';
$odd = !$odd;
$html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="pgpkey">PGP-Key (ASCII-Armored):</label></td><td><textarea name="pgpkey" id="pgpkey">'.filter_output_html($c['pgp_key']).'</textarea></td></tr>';
$odd = !$odd;

$html .= '<tr class="even"><td>&nbsp;</td><td><input type="submit" value="Speichern" /></td></tr>';
$html .= '</table>';



$back = 'list';
if (isset($_REQUEST['back'])) {
    $back = urldecode($_REQUEST['back']);
}
$domainholder = '';
if (isset($_REQUEST['domainholder']) && $_REQUEST['domainholder'] == 1) {
    $domainholder='&domainholder=1';
}

output(html_form('contacts_edit', 'save', 'id='.$_REQUEST['id']."&back=".urlencode($back).$domainholder, $html));

if ($domains) {
    output('<p>Folgende Domains sind von dieser Änderung betroffen:</p>
            <ul>');
    foreach ($domains as $dom) {
        output('<li>'.filter_output_html($dom->fqdn).'</li>');
    }
    output('</ul>');
}

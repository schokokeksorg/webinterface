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

require_once('contacts.php');
require_once('inc/debug.php');
require_once('inc/jquery.php');
javascript();

require_once('session/start.php');


require_role(array(ROLE_CUSTOMER));
$section = 'contacts_list';

$new = False;
if ($_REQUEST['id'] == 'new') {
    title("Adresse anlegen");
    $new = True;
} else {
    title("Adresse bearbeiten");
}

$c = new_contact();
if (! $new) {
    $c = get_contact($_REQUEST['id']);
} elseif (isset($_REQUEST['copy'])) {
    $c = get_contact($_REQUEST['copy']);
    $c['nic_handle'] = NULL;
}

$readonly = '';
// Wenn das Handle beim NIC angemeldet ist, kann man Name und Land nicht mehr ändern
if ($c['nic_handle'] != NULL) {
    $readonly = ' disabled="disabled" ';
    output('<p>Da diese Adresse als möglicher Domaininhaber bei der Domain-Regristry angemeldet ist, können Name/Firmenname und Land nicht mehr geändert werden. Legen Sie ggf. eine neue Adresse an und ändern Sie den Domain-Inhaber entsprechend.</p>');
}
$odd = false;
$html = '<table>';
$html .= '    <tr class="'.($odd == true ? 'odd' : 'even').'"><td>Firmenname:</td><td><input type="text" name="firma" id="firma" value="'.$c['company'].'" '.$readonly.' /></td></tr>';
$odd = !$odd;
$html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td>'.($c['company'] ? 'Ansprechpartner' : 'Name').':</td><td><input type="text" name="name" id="name" value="'.$c['name'].'" '.$readonly.' /></td></tr>';
$odd = !$odd;
$html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="adresse">Adresse:</label></td><td><textarea rows="3" name="adresse" id="adresse">'.$c['address'].'</textarea></td></tr>';
$odd = !$odd;
$html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="plz">Land / PLZ:</label></td><td><input size="2" type="text" name="land" id="land" value="'.$c['country'].'" '.$readonly.' />-</strong><input type="text" name="plz" id="plz" value="'.$c['zip'].'"></td></tr>';
$odd = !$odd;
$html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="ort">Ort:</label></td><td><input type="text" name="ort" id="ort" value="'.$c['city'].'"></td></tr>';
$odd = !$odd;


$html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="email">E-Mail-Adresse:</label></td><td><input type="text" name="email" id="email" value="'.$c['email'].'"></td></tr>';
$odd = !$odd;

$html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="telefon">Telefonnummer:</label></td><td><input type="text" name="telefon" id="telefon" value="'.$c['phone'].'"><span id="telefon_feedback"></span></td></tr>';
$odd = !$odd;
$html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="telefon">Mobil:</label></td><td><input type="text" name="mobile" id="mobile" value="'.$c['mobile'].'"><span id="mobile_feedback"></span></td></tr>';
$odd = !$odd;
$html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="telefon">Telefax:</label></td><td><input type="text" name="telefax" id="telefax" value="'.$c['fax'].'"><span id="telefax_feedback"></span></td></tr>';
$odd = !$odd;

$html .= '<tr class="even"><td>&nbsp;</td><td><input type="submit" value="Speichern" /></td></tr>';
$html .= '</table>';


$back = 'list';
if (isset($_REQUEST['back'])) {
    $back = urldecode($_REQUEST['back']);
}
output(html_form('contacts_edit', 'save', 'id='.$_REQUEST['id']."&back=".urlencode($back), $html));


?>

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

require_once('kundendaten.php');

require_role(ROLE_CUSTOMER);

title("Adressen ändern");

output('<p>Die nachfolgende Adresse ist bei uns für Ihre Kundendaten gespeichert. Wenn Sie weitere Informationen ändern möchten, z.B. Name oder Firmierung, wenden Sie sich bitte per E-Mail <a href="mailto:'.config('adminmail').'">an unseren Support</a>. Die Angabe bzw. Änderung einer separaten Rechnungsanschrift ist hier leider nicht möglich, dies bitte auch über unseren Support veranlassen.</p>');


$c = lese_kundendaten();

$odd = false;
$html = '<table>
    <tr class="'.($odd == true ? 'odd' : 'even').'"><td>Kundennummer:</td><td><strong>'.$c['id'].'</strong></td></tr>';
    $odd = !$odd;
if ($c['firma']) {
    $html .= '    <tr class="'.($odd == true ? 'odd' : 'even').'"><td>Firmenname:</td><td><strong>'.$c['firma'].'</strong></td></tr>';
    $odd = !$odd;
    if ($c['nachname']) {
        $html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td>Verwalter:</td><td><strong>'.$c['vorname'].' '.$c['nachname'].'</strong></td></tr>';
        $odd = !$odd;
    }
} else {
    $html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td>Name:</td><td><strong>'.$c['vorname'].' '.$c['nachname'].'</strong></td></tr>';
    $odd = !$odd;
}

$html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="adresse">Adresse:</label></td><td><input type="text" name="adresse" id="adresse" value="'.$c['adresse'].'"></td></tr>';
$odd = !$odd;
$html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="adresse2">Adresse:</label></td><td><input type="text" name="adresse2" id="adresse2" value="'.$c['adresse2'].'"></td></tr>';
$odd = !$odd;
if ($c['adresszusatz']) {
    $html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="adresse">Adresszusatz:</label></td><td><input type="text" name="adresszusatz" id="adresszusatz" value="'.$c['adresszusatz'].'"></td></tr>';
    $odd = !$odd;
}
$html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="plz">PLZ:</label></td><td><strong>'.$c['land'].'-</strong><input type="text" name="plz" id="plz" value="'.$c['plz'].'"></td></tr>';
$odd = !$odd;
$html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="ort">Ort:</label></td><td><input type="text" name="ort" id="ort" value="'.$c['ort'].'"></td></tr>';
$odd = !$odd;


$html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="email">E-Mail-Adresse:</label></td><td><input type="text" name="email" id="email" value="'.$c['email'].'"></td></tr>';
$odd = !$odd;
if (have_module('newsletter')) {
    $html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="email_newsletter">E-Mail-Adresse (Newsletter):</label></td><td><strong>'.$c['email_newsletter'].'</strong> (Änderung '.internal_link('../newsletter/newsletter', 'hier').')</td></tr>';
    $odd = !$odd;
}
$html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="email_extern">E-Mail-Adresse (extern):</label></td><td><input type="text" name="email_extern" id="email_extern" value="'.$c['email_extern'].'"></td></tr>';
$odd = !$odd;
$html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="email_rechnung">E-Mail-Adresse (Rechnungen):</label></td><td><input type="text" name="email_rechnung" id="email_rechnung" value="'.$c['email_rechnung'].'"></td></tr>';
$odd = !$odd;

$html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="telefon">Telefonnummer:</label></td><td><input type="text" name="telefon" id="telefon" value="'.$c['telefon'].'"></td></tr>';
$odd = !$odd;
$html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="telefon">Mobil:</label></td><td><input type="text" name="mobile" id="mobile" value="'.$c['mobile'].'"></td></tr>';
$odd = !$odd;
$html .= '<tr class="'.($odd == true ? 'odd' : 'even').'"><td><label for="telefon">Telefax:</label></td><td><input type="text" name="telefax" id="telefax" value="'.$c['telefax'].'"></td></tr>';
$odd = !$odd;

$html .= '<tr class="even"><td>&nbsp;</td><td><input type="submit" value="Speichern" /></td></tr>';
$html .= '</table>';


output(html_form('chdetails_all', 'chdetails_save', '', $html));


?>

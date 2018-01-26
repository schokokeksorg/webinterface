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
require_once('inc/icons.php');

require_once('session/start.php');


require_role(array(ROLE_CUSTOMER));

title("Adressen verwalten");


$contacts = get_contacts();
$kundenkontakte = get_kundenkontakte();

output('<p>Sie haben aktuell diese Adressen gespeichert:</p>
<div class="contact-list">');

$liste = array_merge(array_filter(array($kundenkontakte['kunde'], $kundenkontakte['rechnung'], $kundenkontakte['extern'])), array_keys($contacts));
$already_displayed = array();
foreach ($liste as $id) {
    if (in_array($id, $already_displayed)) {
        continue;
    }
    $already_displayed[] = $id;
    $cssclass = 'contact-mainlist ';
    $contact = $contacts[$id];
    $usage = array();
    if ($id == $kundenkontakte['kunde']) {
        $cssclass .= 'mainaddress';
        $usage[] = 'Stamm-Adresse';
    }
    if ($id == $kundenkontakte['extern']) {
        $usage[] = 'Ersatz-Adresse';
    }
    if ($id == $kundenkontakte['rechnung'] || ($id == $kundenkontakte['kunde'] && $kundenkontakte['rechnung'] == NULL)) {
        $usage[] = 'Rechnungs-Adresse';
    }
    if (is_domainholder($id)) {
        $usage[] = 'Domain-Kontakt';
    }
    if ($usage) {
        $usage = "Verwendet als ".join(', ', $usage);
    } else {
        $usage = "Zur Zeit unbenutzt";
    }
    output(internal_link('useas', display_contact($contact, "<p class=\"contact-usage\">$usage</p>", $cssclass), 'id='.$contact['id'], 'class="contacts-choose"'));
}
output("</div><br />");
addnew('edit', 'Neue Adresse erstellen', 'id=new');


?>

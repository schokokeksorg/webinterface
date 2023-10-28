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
require_once('inc/icons.php');

require_once('session/start.php');


require_role([ROLE_CUSTOMER]);

title("Adressen verwalten");


$contacts = get_contacts();
$kundenkontakte = get_kundenkontakte();

output('<p>Sie haben aktuell diese Adressen gespeichert:</p>
<div class="contact-list">');

$liste = array_merge(array_filter([$kundenkontakte['kunde'], $kundenkontakte['rechnung'], $kundenkontakte['extern']]), array_keys($contacts));
$already_displayed = [];
foreach ($liste as $id) {
    if (in_array($id, $already_displayed)) {
        continue;
    }
    $already_displayed[] = $id;
    $cssclass = 'contact-mainlist ';
    if (!isset($contacts[$id])) {
        system_failure('Fehlerhafte Kunden-Zuordnung bei den Kontakten');
    }
    $contact = $contacts[$id];
    $usage = [];
    if ($id == $kundenkontakte['kunde']) {
        $cssclass .= 'mainaddress';
        $usage[] = 'Stamm-Adresse';
    }
    if ($id == $kundenkontakte['extern']) {
        $usage[] = 'Ersatz-Adresse';
    }
    if ($id == $kundenkontakte['rechnung'] || ($id == $kundenkontakte['kunde'] && $kundenkontakte['rechnung'] == null)) {
        $usage[] = 'Rechnungs-Adresse';
    }
    if ($id == $kundenkontakte['dataprotection']) {
        $usage[] = 'Datenschutzbeauftragter';
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

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

$liste = array_merge(array($kundenkontakte['kunde']), array_keys($contacts));
$kundenadresse_displayed = false;
foreach ($liste as $id) {
    if ($kundenadresse_displayed && $id == $kundenkontakte['kunde']) {
        continue;
    }
    $cssclass = '';
    $contact = $contacts[$id];
    $usage = array();
    if ($id == $kundenkontakte['kunde']) {
        $cssclass='mainaddress';
        $kundenadresse_displayed = true;
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
    $actions = array();
    $actions[] = internal_link('edit', icon_edit('Adresse bearbeiten')." Bearbeiten", 'id='.$contact['id']);
    if ($id != $kundenkontakte['kunde'] && ! is_domainholder($id)) {
        // Die Stamm-Adresse kann man nicht löschen und verwendete Domain-Kontakte auch nicht
        $actions[] = internal_link('save', icon_delete()." Löschen", 'action=delete&id='.$contact['id']);
    }
    $actions[] = internal_link('edit', other_icon('page_copy.png')." Kopie erstellen", 'id=new&copy='.$contact['id']);
    $actions[] = internal_link('useas', other_icon('attach.png')." Benutzen als...", 'id='.$contact['id']);
    
    output(display_contact($contact, "<p class=\"contact-usage\">$usage</p><p class=\"contact-actions\">".implode("<br>\n", $actions)."</p>", $cssclass));
}
output("</div><br />");
addnew('edit', 'Neue Adresse erstellen', 'id=new');


?>

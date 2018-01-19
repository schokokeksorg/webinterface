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
foreach ($contacts as $id => $contact) {
    $adresse = nl2br("\n".$contact['address']."\n".$contact['country'].'-'.$contact['zip'].' '.$contact['city']);
    if (! $contact['city']) {
        $adresse = '';
    }
    $usage = array();
    if ($id == $kundenkontakte['kunde']) {
        $usage[] = 'Stamm-Adresse';
    }
    if ($id == $kundenkontakte['extern']) {
        $usage[] = 'Ersatz-Adresse';
    }
    if ($id == $kundenkontakte['rechnung']) {
        $usage[] = 'Rechnungs-Adresse';
    }
    if ($contact['nic_handle']) {
        $usage[] = 'Domain-Kontakt';
    }
    $usage = join(', ', $usage);
    $name = $contact['name'];
    if ($contact['company']) {
        $name = $contact['company']."<br />".$contact['name'];
    }
    $email = $contact['email'];
    $new_email = update_pending($id);
    if ($new_email) {
        $email = "<strike>$email</strike><br/>".$new_email.footnote('Die E-Mail-Adresse wurde noch nicht bestätigt');
    }
    $actions = array();
    $actions[] = internal_link('edit', icon_edit('Adresse bearbeiten')." Bearbeiten", 'id='.$contact['id']);
    if ($id != $kundenkontakte['kunde'] && ! is_domainholder($id)) {
        // Die Stamm-Adresse kann man nicht löschen und verwendete Domain-Kontakte auch nicht
        $actions[] = internal_link('save', icon_delete('Adresse löschen')." Löschen", 'action=delete&id='.$contact['id']);
    }
    $actions[] = internal_link('edit', other_icon('page_copy.png', 'Kopie erstellen')." Kopie erstellen", 'id=new&copy='.$contact['id']);
        
    $email = implode("<br>\n", array_filter(array($email, $contact['phone'], $contact['fax'], $contact['mobile'])));
    output("<div class=\"contact\" id=\"contact-{$contact['id']}\"><p class=\"contact-id\">#{$contact['id']}</p><p class=\"contact-address\"><strong>$name</strong>$adresse</p><p class=\"contact-contact\">$email</p><p class=\"contact-usage\">Verwendung als $usage</p><p class=\"contact-actions\">".implode(' ', $actions)."</p></div>");
}
output("</div><br />");
addnew('edit', 'Neuen Kontakt erstellen', 'id=new');


?>

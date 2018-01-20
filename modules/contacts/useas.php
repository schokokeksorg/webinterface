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
require_once('inc/jquery.php');
#javascript();

require_once('session/start.php');


require_role(array(ROLE_CUSTOMER));
$section = 'contacts_list';

title("Adresse verwenden als...");


output(internal_link('list', 'Zurück zur Übersicht'));

$contact = get_contact($_REQUEST['id']);
$kundenkontakte = get_kundenkontakte();
$id = $contact['id'];


if (isset($_REQUEST['useas'])) {
    if ($_REQUEST['useas'] == 'kunde') {
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete') {
            system_failure("Man kann eine Kunden-Adresse nicht löschen, bitte eine neue als Ersatz festlegen!");
        } else {
            set_kundenkontakt('kunde', $id);
            redirect('useas?id='.$id);
        }
    }
    if ($_REQUEST['useas'] == 'extern') {
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete') {
            set_kundenkontakt('extern', NULL);
            redirect('useas?id='.$id);
        } else {
            set_kundenkontakt('extern', $id);
            redirect('useas?id='.$id);
        }
    }
    if ($_REQUEST['useas'] == 'rechnung') {
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete') {
            set_kundenkontakt('rechnung', NULL);
            redirect('useas?id='.$id);
        } else {
            set_kundenkontakt('rechnung', $id);
            redirect('useas?id='.$id);
        }
    }
} else {
    $adresse = nl2br("\n".$contact['address']."\n".$contact['country'].'-'.$contact['zip'].' '.$contact['city']);
    if (! $contact['city']) {
        $adresse = '';
    }
    $name = $contact['name'];
    if ($contact['company']) {
        $name = $contact['company']."<br />".$contact['name'];
    }
    $email = implode("<br>\n", array_filter(array($contact['email'], $contact['phone'], $contact['fax'], $contact['mobile'])));

    $contact_string = "<div class=\"contact\" id=\"contact-{$contact['id']}\"><p class=\"contact-id\">#{$contact['id']}</p><p class=\"contact-address\"><strong>$name</strong>$adresse</p><p class=\"contact-contact\">$email</p></div>";

    output($contact_string);

    output('<h4>Verwendung als Kundenkontakt</h4>');
    if ($id == $kundenkontakte['kunde']) {
        output("<p>Diese Adresse ist die Stamm-Adresse!</p>");
    } else {
        if (possible_kundenkontakt($contact)) {
            addnew('useas', 'Diese Adresse als Haupt-Adresse des Kontoinhabers festlegen.', 'id='.$_REQUEST['id'].'&useas=kunde');
        }
        if ($id == $kundenkontakte['extern']) {
            output("<p>Diese Adresse ist die Ersatz-Adresse bei Störungen! ".icon_delete().internal_link('useas', "Zuordnung löschen", 'id='.$_REQUEST['id'].'&useas=extern&action=delete')."</p>");
        } else {
            addnew('useas', 'Diese Adresse als Ersatz-Adresse des Kontoinhabers für Störungen festlegen.', 'id='.$_REQUEST['id'].'&useas=extern');
        }
        if ($id == $kundenkontakte['rechnung']) {
            output("<p>Diese Adresse ist die Rechnungs-Adresse. ".icon_delete().internal_link('useas', "Zuordnung löschen", 'id='.$_REQUEST['id'].'&useas=rechnung&action=delete')."</p>");
        } else {
            addnew('useas', 'Diese Adresse als Rechnungs-Adresse festlegen.', 'id='.$_REQUEST['id'].'&useas=rechnung');
        }
    }


    if (possible_domainholder($contact)) {
        output("<h4>Verwendung als Domaininhaber bzw. -Verwalter</h4>");

        output("<p>Kann als Domaininhaber verwendet werden!</p>");
    }


}

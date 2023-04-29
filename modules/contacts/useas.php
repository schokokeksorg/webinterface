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
            //redirect('useas?id='.$id);
            // usability: Dann kann man da eh nichts mehr machen, also zurück zur Übersicht
            redirect('list');
        }
    }
    if ($_REQUEST['useas'] == 'extern') {
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete') {
            set_kundenkontakt('extern', null);
            redirect('useas?id='.$id);
        } else {
            set_kundenkontakt('extern', $id);
            redirect('useas?id='.$id);
        }
    }
    if ($_REQUEST['useas'] == 'rechnung') {
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete') {
            set_kundenkontakt('rechnung', null);
            redirect('useas?id='.$id);
        } else {
            set_kundenkontakt('rechnung', $id);
            redirect('useas?id='.$id);
        }
    }
    if ($_REQUEST['useas'] == 'dataprotection') {
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete') {
            set_kundenkontakt('dataprotection', null);
            redirect('useas?id='.$id);
        } else {
            set_kundenkontakt('dataprotection', $id);
            redirect('useas?id='.$id);
        }
    }
} else {
    output(display_contact($contact));
    output('<p>'.internal_link('edit', icon_edit('Adresse bearbeiten')." Adresse bearbeiten", 'id='.$id).'</p>');
    if ($id != $kundenkontakte['kunde'] && ! is_domainholder($id)) {
        // Die Stamm-Adresse kann man nicht löschen und verwendete Domain-Kontakte auch nicht
        output('<p class="delete">'.internal_link('save', "Diese Adresse löschen", 'action=delete&id='.$id).'</p>');
    }

    output('<h4>Verwendung als Kundenkontakt</h4>');
    if ($id == $kundenkontakte['kunde']) {
        output("<p>Diese Adresse ist die Stamm-Adresse!</p>");
    } else {
        if (possible_kundenkontakt($contact)) {
            addnew('useas', 'Diese Adresse als Haupt-Adresse des Kontoinhabers festlegen.', 'id='.$_REQUEST['id'].'&useas=kunde');
        }
        if ($id == $kundenkontakte['extern']) {
            output("<p>Diese Adresse ist die Ersatz-Adresse bei Störungen. ".icon_delete().internal_link('useas', "Zuordnung löschen", 'id='.$_REQUEST['id'].'&useas=extern&action=delete')."</p>");
        } else {
            addnew('useas', 'Diese Adresse als Ersatz-Adresse des Kontoinhabers für Störungen festlegen.', 'id='.$_REQUEST['id'].'&useas=extern');
        }
        if ($id == $kundenkontakte['rechnung']) {
            output("<p>Diese Adresse ist die Rechnungs-Adresse. ".icon_delete().internal_link('useas', "Zuordnung löschen", 'id='.$_REQUEST['id'].'&useas=rechnung&action=delete')."</p>");
        } else {
            addnew('useas', 'Diese Adresse als Rechnungs-Adresse festlegen.', 'id='.$_REQUEST['id'].'&useas=rechnung');
        }
        if ($id == $kundenkontakte['dataprotection']) {
            output("<p>Diese Adresse ist die Adresse des betrieblichen Datenschutzbeauftragten. ".icon_delete().internal_link('useas', "Zuordnung löschen", 'id='.$_REQUEST['id'].'&useas=dataprotection&action=delete')."</p>");
        } else {
            addnew('useas', 'Diese Adresse als betrieblichen Datenschutzbeauftragten benennen.', 'id='.$_REQUEST['id'].'&useas=dataprotection');
        }
    }


    output("<h4>Verwendung als Domaininhaber bzw. -Verwalter</h4>");
    if (possible_domainholder($contact)) {
        $domains = domainlist_by_contact($contact);
        foreach ($domains as $d) {
            $funktion = [];
            if ($contact['id'] == $d->owner) {
                $funktion[] = 'Inhaber';
            }
            if ($contact['id'] == $d->admin_c) {
                $funktion[] = 'Verwalter';
            }
            $funktion = implode(' und ', $funktion);

            output('<p>Ist <strong>'.$funktion.'</strong> bei der Domain <strong>'.$d->fqdn.'</strong>. '.internal_link('../domains/detail', icon_edit()." Inhaber dieser Domain ändern", 'id='.$d->id).'</p>');
        }
    } else {
        output("<p>Zur Verwendung als Domaininhaber müssen Name, vollständige Adresse, E-Mail-Adresse sowie Telefonnummer angegeben sein.</p>");
    }
}

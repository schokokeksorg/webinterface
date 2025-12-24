<?php

/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('contactapi.php');
require_once('inc/base.php');

require_once('contacts.php');

if (!isset($_SESSION['contacts_choose_key'])) {
    system_failure("Ungültiger Aufruf dieser Funktion");
}
$sesskey = $_SESSION['contacts_choose_key'];

if (isset($_REQUEST['id'])) {
    $c = get_contact($_REQUEST['id']);
    $_SESSION[$sesskey] = $c['id'];
    $redirect = $_SESSION['contacts_choose_redirect'];
    unset($_SESSION['contacts_choose_header']);
    unset($_SESSION['contacts_choose_key']);
    unset($_SESSION['contacts_choose_redirect']);
    redirect($redirect);
}


title('Kontakt auswählen');
$section = 'contacts_list';

if (isset($_SESSION['contacts_choose_header'])) {
    output($_SESSION['contacts_choose_header']);
} else {
    output('Wählen Sie einen Kontakt aus!');
}


output('<div class="contact-list">');
$contacts = get_contacts();
$have_invalid = false;
foreach ($contacts as $c) {
    if (possible_domainholder($c, $_SESSION['contacts_choose_domainname'])) {
        output(internal_link('', display_contact($c), "id={$c['id']}", 'class="contacts-choose"'));
    } else {
        $have_invalid = true;
        output(display_contact($c, '<p><em>Datensatz unvollständig</em></p>'));
    }
}

output('</div>');

if ($have_invalid) {
    warning('Sie haben Kontakte, die nicht als Domaininhaber gewählt werden können.
    Zur Verwendung als Domaininhaber müssen Name, vollständige Adresse, E-Mail-Adresse sowie Telefonnummer angegeben sein.');
}

addnew('edit', 'Neue Adresse erstellen', 'id=new&back=choose&domainholder=1');

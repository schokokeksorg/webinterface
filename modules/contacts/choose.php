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

require_once('contactapi.php');
require_once('inc/base.php');

require_once('contacts.php');

if (! isset($_SESSION['contacts_choose_key'])) {
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
$contacts = possible_domainholders();
foreach ($contacts as $c) {
    output(internal_link('', display_contact($c), "id={$c['id']}", 'class="contacts-choose"'));
}

output('</div>');

addnew('edit', 'Neue Adresse erstellen', 'id=new&back=choose&domainholder=1');





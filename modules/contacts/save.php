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
require_once('vendor/autoload.php');

require_once('session/start.php');


require_role(array(ROLE_CUSTOMER));
$section = 'contacts_list';

check_form_token('contacts_edit');

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
}

if ($c['nic_handle'] != NULL) {
    if (c['name'] != $_REQUEST['name'] || $c['company'] != $_REQUEST['firma'] || $c['country'] != $_REQUEST['land']) {
        system_failure('Name/Firma/Land kann bei diesem Kontakt nicht geändert werden.');
    }
}


$c['company'] = maybe_null($_REQUEST['firma']);
$c['name'] = maybe_null($_REQUEST['name']);
$c['address'] = maybe_null($_REQUEST['adresse']);
$c['country'] = maybe_null(strtoupper($_REQUEST['land']));
$c['zip'] = maybe_null($_REQUEST['plz']);
$c['city'] = maybe_null($_REQUEST['ort']);

    
if ($_REQUEST['telefon']) {
    $phoneNumberUtil = \libphonenumber\PhoneNumberUtil::getInstance();
    try {
        $phoneNumber = $phoneNumberUtil->parse($_REQUEST['telefon'], $_REQUEST['land'], null, true);
    } catch (Exception $e) {
        system_failure('Die eingegebene Telefonnummer scheint nicht gültig zu sein!');
    }
    if ($phoneNumberUtil->isValidNumber($phoneNumber)) {
        $c['phone'] = $phoneNumberUtil->format($phoneNumber, 1);
    } else {
        system_failure('Die eingegebene Telefonnummer scheint nicht gültig zu sein!');
        $c['phone'] = NULL;
    }
} else {
    $c['phone'] = NULL;
}
//$c['phone'] = maybe_null($_REQUEST['telefon']);
$c['mobile'] = maybe_null($_REQUEST['mobile']);
$c['fax'] = maybe_null($_REQUEST['telefax']);

// FIXME: PGP-ID/Key fehlen

// Zuerst Kontakt speichern und wenn eine Änderung der E-Mail gewünscht war,
// dann hinterher das Token erzeugen und senden. Weil wir für das Token die 
// Contact-ID brauchen und die bekommen wir bei einer Neueintragung erst nach 
// dem Speichern.

$id = save_contact($c);
$c['id'] = $id;

if ($c['email'] != $_REQUEST['email']) {
    if (have_mailaddress($_REQUEST['email'])) {
        save_emailaddress($c['id'], $_REQUEST['email']);
    } else {
        send_emailchange_token($c['id'], $_REQUEST['email']);
    }
}


if (! $debugmode)
    header("Location: list");

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
require_once('numbers.php');
require_once('inc/debug.php');

require_once('session/start.php');


require_role(array(ROLE_CUSTOMER));
$section = 'contacts_list';

$back = 'list';
if (isset($_REQUEST['back'])) {
    $back = urldecode($_REQUEST['back']);
}

if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete') {
    $contact = get_contact($_REQUEST['id']);
    
    $contact_string = display_contact($contact);

    $sure = user_is_sure();
    if ($sure === NULL)
    {
       are_you_sure("action=delete&id={$contact['id']}&back=".urlencode($back), "Möchten Sie diese Adresse wirklich löschen? {$contact_string}");
    }
    elseif ($sure === true)
    {
       delete_contact($contact['id']);
       if (! $debugmode)
           header("Location: ".$back);
    }
    elseif ($sure === false)
    {
        if (! $debugmode)
            header("Location: ".$back);
    }


} else {
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

    if (!isset($_REQUEST['firma'])) {
        $_REQUEST['firma'] = $c['company'];
    }
    if (!isset($_REQUEST['name'])) {
        $_REQUEST['name'] = $c['name'];
    }
    if (!isset($_REQUEST['land'])) {
        $_REQUEST['land'] = $c['country'];
    }

    if ($c['nic_handle'] != NULL) {
        if ($c['name'] != $_REQUEST['name'] || $c['company'] != $_REQUEST['firma'] || $c['country'] != $_REQUEST['land']) {
            system_failure('Name/Firma/Land kann bei diesem Kontakt nicht geändert werden.');
        }
    }

    $kundenkontakte = get_kundenkontakte();
    if ($c['id'] == $kundenkontakte['kunde']) {
        if (!$_REQUEST['name'] && !$_REQUEST['firma']) {
            system_failure('Beim Inhaber darf nicht Firmenname und Name leer sein.');
        }
    }

    $c['company'] = verify_input_general(maybe_null($_REQUEST['firma']));
    $c['name'] = verify_input_general(maybe_null($_REQUEST['name']));
    $c['address'] = verify_input_general(maybe_null($_REQUEST['adresse']));
    $c['country'] = verify_input_general(maybe_null(strtoupper($_REQUEST['land'])));
    $c['zip'] = verify_input_general(maybe_null($_REQUEST['plz']));
    $c['city'] = verify_input_general(maybe_null($_REQUEST['ort']));

        

    if ($_REQUEST['telefon']) {
        $num = format_number(verify_input_general($_REQUEST['telefon']), $_REQUEST['land']);
        if ($num) {
            $c['phone'] = $num;
        } else {
            system_failure('Die eingegebene Telefonnummer scheint nicht gültig zu sein!');
        }
    } else {
        $c['phone'] = NULL;
    }
    if ($_REQUEST['mobile']) {
        $num = format_number(verify_input_general($_REQUEST['mobile']), $_REQUEST['land']);
        if ($num) {
            $c['mobile'] = $num;
        } else {
            system_failure('Die eingegebene Mobiltelefonnummer scheint nicht gültig zu sein!');
        }
    } else {
        $c['mobile'] = NULL;
    }
    if ($_REQUEST['telefax']) {
        $num = format_number(verify_input_general($_REQUEST['telefax']), $_REQUEST['land']);
        if ($num) {
            $c['fax'] = $num;
        } else {
            system_failure('Die eingegebene Faxnummer scheint nicht gültig zu sein!');
        }
    } else {
        $c['fax'] = NULL;
    }

    // FIXME: PGP-ID/Key fehlen

    // Zuerst Kontakt speichern und wenn eine Änderung der E-Mail gewünscht war,
    // dann hinterher das Token erzeugen und senden. Weil wir für das Token die 
    // Contact-ID brauchen und die bekommen wir bei einer Neueintragung erst nach 
    // dem Speichern.

    $id = save_contact($c);
    $c['id'] = $id;

    if ($c['email'] != $_REQUEST['email']) {
        if (have_mailaddress($_REQUEST['email'])) {
            save_emailaddress($c['id'], verify_input_general($_REQUEST['email']));
        } else {
            send_emailchange_token($c['id'], $_REQUEST['email']);
        }
    }
    if ($c['nic_id']) {
        $c = get_contact($c['id']);
        upload_contact($c);
    }


    if (! $debugmode)
        header("Location: ".$back);
}

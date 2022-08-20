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
require_once('numbers.php');
require_once('inc/debug.php');

require_once('session/start.php');


require_role([ROLE_CUSTOMER]);
$section = 'contacts_list';

$back = 'list';
if (isset($_REQUEST['back'])) {
    $back = urldecode($_REQUEST['back']);
}

if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete') {
    $contact = get_contact($_REQUEST['id']);

    $contact_string = display_contact($contact);

    $sure = user_is_sure();
    if ($sure === null) {
        are_you_sure("action=delete&id={$contact['id']}&back=".urlencode($back), "Möchten Sie diese Adresse wirklich löschen? {$contact_string}");
    } elseif ($sure === true) {
        delete_contact($contact['id']);
        if (! $debugmode) {
            header("Location: ".$back);
        }
    } elseif ($sure === false) {
        if (! $debugmode) {
            header("Location: ".$back);
        }
    }
} else {
    check_form_token('contacts_edit');

    $new = false;
    if ($_REQUEST['id'] == 'new') {
        title("Adresse anlegen");
        $new = true;
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

    if ($c['nic_handle'] != null) {
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

    $c['salutation'] = null;
    if ($_REQUEST['salutation'] == 'Herr') {
        $c['salutation'] = 'Herr';
    } elseif ($_REQUEST['salutation'] == 'Frau') {
        $c['salutation'] = 'Frau';
    }
    $c['company'] = filter_input_general(maybe_null($_REQUEST['firma']));
    $c['name'] = filter_input_general(maybe_null($_REQUEST['name']));
    $c['address'] = filter_input_general(maybe_null($_REQUEST['adresse']));
    $c['country'] = filter_input_oneline(maybe_null(strtoupper($_REQUEST['land'])));
    $c['zip'] = filter_input_oneline(maybe_null($_REQUEST['plz']));
    $c['city'] = filter_input_oneline(maybe_null($_REQUEST['ort']));
    if ($new && isset($_REQUEST['email'])) {
        $c['email'] = filter_input_oneline(maybe_null($_REQUEST['email']));
        if (!check_emailaddr($c['email'])) {
            system_failure("Ungültige E-Mail-Adresse!");
        }
    }


    if (isset($_REQUEST['telefon']) && $_REQUEST['telefon'] != '') {
        $num = format_number(filter_input_oneline($_REQUEST['telefon']), $_REQUEST['land']);
        if ($num) {
            $c['phone'] = $num;
        } else {
            system_failure('Die eingegebene Telefonnummer scheint nicht gültig zu sein!');
        }
    } else {
        $c['phone'] = null;
    }
    if (isset($_REQUEST['mobile']) && $_REQUEST['mobile'] != '') {
        $num = format_number(filter_input_oneline($_REQUEST['mobile']), $_REQUEST['land']);
        if ($num) {
            $c['mobile'] = $num;
            if (! $c['phone']) {
                // dupliziere die Mobiltelefonnummer als normale Nummer wegen der Nutzung als Domainhandles
                $c['phone'] = $num;
            }
        } else {
            system_failure('Die eingegebene Mobiltelefonnummer scheint nicht gültig zu sein!');
        }
    } else {
        $c['mobile'] = null;
    }
    if (isset($_REQUEST['telefax']) && $_REQUEST['telefax'] != '') {
        $num = format_number(filter_input_oneline($_REQUEST['telefax']), $_REQUEST['land']);
        if ($num) {
            $c['fax'] = $num;
        } else {
            system_failure('Die eingegebene Faxnummer scheint nicht gültig zu sein!');
        }
    } else {
        $c['fax'] = null;
    }


    if (isset($_REQUEST['usepgp']) && $_REQUEST['usepgp'] == 'yes' && isset($_REQUEST['pgpid'])) {
        $pgpid = preg_replace('/[^0-9a-fA-F]/', '', $_REQUEST['pgpid']);
        DEBUG('PGP-ID: '.$pgpid);
        if (isset($_REQUEST['pgpkey']) && $_REQUEST['pgpkey']) {
            DEBUG('Key angegeben, wird importiert');
            $c['pgp_id'] = $pgpid;
            import_pgp_key($_REQUEST['pgpkey']);
            $c['pgp_key'] = $_REQUEST['pgpkey'];
        } else {
            DEBUG('Kein Key, wird vom Keyserver geholt!');
            $c['pgp_id'] = fetch_pgp_key($pgpid);
        }
        if (!test_pgp_key($c['pgp_id'])) {
            $c['pgp_id'] = null;
            $c['pgp_key'] = null;
            warning('Ihr PGP-Key wurde nicht übernommen, da er nicht gültig zu sein scheint. Bitte geben Sie im Zweifel die vollständige Key-ID (Fingerprint) und einen Key in der ASCII-Form ein.');
        }
    } else {
        $c['pgp_id'] = null;
        $c['pgp_key'] = null;
    }


    if (isset($_REQUEST['domainholder']) && $_REQUEST['domainholder'] == 1) {
        if (!possible_domainholder($c)) {
            DEBUG("Kein möglicher Domaininhaber:");
            DEBUG($c);
            warning('Zur Verwendung als Domaininhaber fehlen noch Angaben.');
            redirect('edit?id='.$_REQUEST['id'].'&back='.$_REQUEST['back'].'&domainholder=1');
        }
        if (isset($_REQUEST['email']) && !have_mailaddress($_REQUEST['email'])) {
            warning("Die neu angelegte Adresse kann erst dann als Domaininhaber genutzt werden, wenn die E-Mail-Adresse bestätigt wurde.");
        }
    }

    $domains = domainlist_by_contact($c);
    if ($domains) {
        if (isset($_REQUEST['email']) && $c['email'] != $_REQUEST['email'] && !(isset($_REQUEST['designated']) && $_REQUEST['designated'] == 'yes')) {
            system_failure("Sie müssen die explizite Zustimmung des Domaininhabers bestätigen um diese Änderungen zu speichern.");
        }
    }

    // Zuerst Kontakt speichern und wenn eine Änderung der E-Mail gewünscht war,
    // dann hinterher das Token erzeugen und senden. Weil wir für das Token die
    // Contact-ID brauchen und die bekommen wir bei einer Neueintragung erst nach
    // dem Speichern.

    $id = save_contact($c);
    $c['id'] = $id;

    if (isset($_REQUEST['email']) && check_emailaddr($_REQUEST['email']) && ($new || $c['email'] != $_REQUEST['email'])) {
        if (have_mailaddress($_REQUEST['email'])) {
            save_emailaddress($c['id'], $_REQUEST['email']);
        } else {
            send_emailchange_token($c['id'], $_REQUEST['email']);
        }
    }
    if ($c['nic_id']) {
        $c = get_contact($c['id']);
        upload_contact($c);
    }


    redirect($back);
}

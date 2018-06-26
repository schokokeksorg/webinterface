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

require_once('inc/debug.php');
require_once('inc/icons.php');
require_once('inc/security.php');
require_role(array(ROLE_CUSTOMER));
require_once('class/domain.php');

require_once('contactapi.php');

/*
Todo:
    - Ausgabe-Funktion abstrahieren
    - Telefonnummern bei Ausgabe durch filter_input_general schieben
    - Domaininhaber festlegen
*/


function new_contact()
{
    return array("id" => null,
        "state" => null,
        "lastchange" => time(),
        "nic_handle" => null,
        "nic_id" => null,
        "company" => null,
        "name" => null,
        "address" => null,
        "zip" => null,
        "city" => null,
        "country" => "DE",
        "phone" => null,
        "mobile" => null,
        "fax" => null,
        "email" => null,
        "pgp_id" => null,
        "pgp_key" => null,
        "customer" => $_SESSION['customerinfo']['customerno']);
}


function get_contact($id)
{
    $args = array(
        "cid" => (int) $_SESSION['customerinfo']['customerno'],
        "id" => (int) $id);
    $result = db_query("SELECT id, state, lastchange, nic_id, nic_handle, company, name, address, zip, city, country, phone, mobile, fax, email, pgp_id, pgp_key FROM kundendaten.contacts WHERE id=:id AND customer=:cid", $args);
    if ($result->rowCount() == 0) {
        system_failure("Kontakt nicht gefunden oder gehört nicht diesem Kunden");
    }
    $contact = $result->fetch();
    return $contact;
}

function get_contacts()
{
    $cid = (int) $_SESSION['customerinfo']['customerno'];
    $result = db_query("SELECT id, state, lastchange, nic_id, nic_handle, company, name, address, zip, city, country, phone, mobile, fax, email, pgp_id, pgp_key FROM kundendaten.contacts WHERE (state<>'deleted' OR state IS NULL) AND customer=? ORDER BY COALESCE(company, name)", array($cid));
    $ret = array();
    while ($contact = $result->fetch()) {
        $ret[$contact['id']] = $contact;
    }
    DEBUG($ret);
    return $ret;
}


function is_domainholder($contactid)
{
    $contactid = (int) $contactid;
    $result = db_query("SELECT id FROM kundendaten.domains WHERE owner=? OR admin_c=?", array($contactid, $contactid));
    if ($result->rowCount() > 0) {
        return true;
    }
    return false;
}

function possible_domainholders()
{
    $allcontacts = get_contacts();
    $ret = array();
    foreach ($allcontacts as $id => $c) {
        if (possible_domainholder($c)) {
            $ret[$id] = $c;
        }
    }
    return $ret;
}

function possible_domainholder($c)
{
    if ($c['name'] && $c['address'] && $c['zip'] && $c['city'] && $c['country'] && $c['phone'] && $c['email']) {
        return true;
    }
    return false;
}

function have_mailaddress($email)
{
    $cid = (int) $_SESSION['customerinfo']['customerno'];
    $result = db_query("SELECT id FROM kundendaten.contacts WHERE customer=? AND email=?", array($cid, $email));
    if ($result->rowCount() > 0) {
        return true;
    }
    return false;
}


function possible_kundenkontakt($c)
{
    if ($c['name'] && $c['email']) {
        return true;
    }
}


function set_kundenkontakt($typ, $id)
{
    if (! $id) {
        $id = null;
    } else {
        $id = (int) $id;
    }
    $args = array(
        "kunde" => (int) $_SESSION['customerinfo']['customerno'],
        "contact" => $id
        );
    $field = null;
    if ($typ == 'kunde') {
        $field = 'contact_kunde';
    } elseif ($typ == 'extern') {
        $field = 'contact_extern';
    } elseif ($typ == 'rechnung') {
        $field = 'contact_rechnung';
    } elseif ($typ == 'dataprotection') {
        $field = 'contact_dataprotection';
    } else {
        system_failure("Falscher Typ!");
    }
    db_query("UPDATE kundendaten.kunden SET ".$field."=:contact WHERE id=:kunde", $args);
    sync_legacy_contactdata();
}


function sync_legacy_contactdata()
{
    $cid = (int) $_SESSION['customerinfo']['customerno'];
    $kundenkontakte = get_kundenkontakte();
    $kunde = get_contact($kundenkontakte['kunde']);
    $vorname = null;
    $nachname = null;
    if ($kunde['name']) {
        $vorname = explode(' ', $kunde['name'], 2)[0];
        $nachname = explode(' ', $kunde['name'], 2)[1];
    }
    $args = array("firma" => $kunde['company'],
            "vorname" => $vorname,
            "nachname" => $nachname,
            "adresse" => $kunde['address'],
            "plz" => $kunde['zip'],
            "ort" => $kunde['city'],
            "land" => $kunde['country'],
            "telefon" => $kunde['phone'],
            "mobile" => $kunde['mobile'],
            "telefax" => $kunde['fax'],
            "email" => $kunde['email'],
            "pgp_id" => $kunde['pgp_id'],
            "pgp_key" => $kunde['pgp_key'],
            "cid" => $cid);
    db_query("UPDATE kundendaten.kunden SET firma=:firma, vorname=:vorname, nachname=:nachname, adresse=:adresse,
            plz=:plz, ort=:ort, land=:land, telefon=:telefon, mobile=:mobile, telefax=:telefax, email=:email, 
            pgp_id=:pgp_id, pgp_key=:pgp_key WHERE id=:cid", $args);
    if ($kundenkontakte['extern']) {
        $extern = get_contact($kundenkontakte['extern'])['email'];
        if ($extern) {
            db_query("UPDATE kundendaten.kunden SET email_extern=? WHERE id=?", array($extern, $cid));
        }
    }
    if ($kundenkontakte['rechnung']) {
        $kunde = get_contact($kundenkontakte['rechnung']);
        $args = array("firma" => $kunde['company'],
                "name" => $kunde['name'],
                "adresse" => $kunde['address'],
                "plz" => $kunde['zip'],
                "ort" => $kunde['city'],
                "email" => $kunde['email'],
                "cid" => $cid);
        db_query("UPDATE kundendaten.kunden SET re_firma=:firma, re_name=:name, re_adresse=:adresse,
                re_plz=:plz, re_ort=:ort, email_rechnung=:email WHERE id=:cid", $args);
    }
}


function get_kundenkontakte()
{
    $cid = (int) $_SESSION['customerinfo']['customerno'];
    $result = db_query("SELECT contact_kunde, contact_extern, contact_rechnung, contact_dataprotection FROM kundendaten.kunden WHERE id=?", array($cid));
    $res = $result->fetch();
    $ret = array("kunde" => $res['contact_kunde'],
                 "extern" => $res['contact_extern'],
                 "rechnung" => $res['contact_rechnung'],
                 "dataprotection" => $res['contact_dataprotection']);
    return $ret;
}

function save_emailaddress($id, $email)
{
    // Speichert eine E-Mail-Adresse direkt, z.B. wenn diese schonmal geprüft wurde
    $args = array("cid" => (int) $_SESSION['customerinfo']['customerno'],
        "id" => (int) $id,
        "email" => $email);
    db_query("UPDATE kundendaten.contacts SET email=:email WHERE id=:id AND customer=:cid", $args);
}

function save_contact($c)
{
    if ($c['nic_id']) {
        if (! possible_domainholder($c)) {
            system_failure("Sie haben ein Feld geleert, das für die Eigenschaft als Domaininhaber erhalten bleiben muss. Ihre Änderungen wurden nicht gespeichert.");
        }
    }
    for ($i=0;array_key_exists($i, $c);$i++) {
        unset($c[$i]);
    }
    unset($c['state']);
    unset($c['lastchange']);
    unset($c['email']);
    $c['customer'] = (int) $_SESSION['customerinfo']['customerno'];
    if ($c['id']) {
        // Kontakt bestaht schon, Update
        db_query("UPDATE kundendaten.contacts SET nic_id=:nic_id, nic_handle=:nic_handle, company=:company, name=:name, address=:address, zip=:zip, city=:city, country=:country, phone=:phone, mobile=:mobile, fax=:fax, pgp_id=:pgp_id, pgp_key=:pgp_key WHERE id=:id AND customer=:customer", $c);
    } else {
        unset($c['id']);
        // Neu anlegen
        db_query("INSERT INTO kundendaten.contacts (nic_id, nic_handle, customer, company, name, address, zip, city, country, phone, mobile, fax, pgp_id, pgp_key) VALUES (:nic_id, :nic_handle, :customer, :company, :name, :address, :zip, :city, :country, :phone, :mobile, :fax, :pgp_id, :pgp_key)", $c);
        $c['id'] = db_insert_id();
    }
    // FIXME: Das sollte eigentlich nicht bei jedem einzelnen Speicherovrgang passieren
    sync_legacy_contactdata();
    return $c['id'];
}


function send_emailchange_token($id, $email)
{
    if (! check_emailaddr($email)) {
        system_falure("Die E-Mail-Adresse scheint nicht gültig zu sein.");
    }
    $args = array("id" => (int) $id,
        "email" => $email,
        "token" => random_string(20));

    db_query("INSERT INTO kundendaten.mailaddress_token (token, expire, contact, email) VALUES (:token, NOW() + INTERVAL 1 DAY, :id, :email)", $args);
    DEBUG('Token erzeugt: '.print_r($args, true));
    $message = 'Diese E-Mail-Adresse wurde angegeben als möglicher Domaininhaber oder Kundenkontakt bei schokokeks.org Hosting.

Bitte bestätigen Sie mit einem Klick auf den nachfolgenden Link, dass diese E-Mail-Adresse funktioniert und verwendet werden soll:

    '.config('webinterface_url').'/verify'.$args['token'].'

Wenn Sie diesen Link nicht innerhalb von 24 Stunden abrufen, wird Ihre Adresse gelöscht und nicht verwendet.
Sollten Sie mit der Verwendung Ihrer E-Mail-Adresse nicht einverstanden sein, so ignorieren Sie daher bitte diese Nachricht oder teilen Sie uns dies mit.

-- 
schokokeks.org GbR, Bernd Wurst, Johannes Böck
Köchersberg 32, 71540 Murrhardt

https://schokokeks.org
';
    # send welcome message
    mail($email, '=?UTF-8?Q?Best=C3=A4tigung_Ihrer_E-Mail-Adresse?=', $message, "X-schokokeks-org-message: verify\nFrom: ".config('company_name').' <'.config('adminmail').">\nMIME-Version: 1.0\nContent-Type: text/plain; charset=UTF-8\n");
}

function update_pending($contactid)
{
    $contactid = (int) $contactid;
    $result = db_query("SELECT email FROM kundendaten.mailaddress_token WHERE contact=?", array($contactid));
    if ($result->rowCount() == 0) {
        return null;
    }
    $res = $result->fetch();
    return $res['email'];
}



function delete_contact($id)
{
    $c = get_contact($id);
    $kundenkontakte = get_kundenkontakte();
    if ($id == $kundenkontakte['kunde']) {
        system_failure("Die Stamm-Adresse kann nicht gelöscht werden, bitte erst eine andere Adresse als Stamm-Adresse festlegen!");
    }
    if ($id == $kundenkontakte['rechnung']) {
        set_kundenkontakt('rechnung', null);
    }
    if ($id == $kundenkontakte['extern']) {
        set_kundenkontakt('extern', null);
    }
    if ($c['nic_id']) {
        // Lösche bei der Registry
        $c['state'] = 'deleted';
        upload_contact($c);
    }
    db_query("UPDATE kundendaten.contacts SET state='deleted' WHERE id=?", array($c['id']));
}


function search_pgp_key($search)
{
    if (! check_emailaddr($search)) {
        # Keine Ausgabe weil diese Funktion im AJAX-Call verwendet wird
        return null;
    }
    $output = array();
    exec('LC_ALL=C /usr/bin/gpg --batch --with-colons --keyserver hkp://pool.sks-keyservers.net --search-key '.escapeshellarg($search), $output);
    DEBUG($output);
    $keys = array();
    foreach ($output as $row) {
        if (substr($row, 0, 4) === 'pub:') {
            $parts = explode(':', $row);
            if ($parts[5] && ($parts[5] < time())) {
                // abgelaufener key
                continue;
            }
            // array-key = create-timestamp
            // array-value = key-id
            $keys[$parts[4]] = $parts[1];
        }
    }
    if (count($keys) == 0) {
        return null;
    }
    ksort($keys, SORT_NUMERIC);
    DEBUG(end($keys));
    // liefert den neuesten Key
    return end($keys);
}


function fetch_pgp_key($pgp_id)
{
    $output = array();
    $ret = null;
    DEBUG('/usr/bin/gpg --batch --keyserver hkp://pool.sks-keyservers.net --recv-key '.escapeshellarg($pgp_id));
    exec('/usr/bin/gpg --batch --keyserver hkp://pool.sks-keyservers.net --recv-key '.escapeshellarg($pgp_id), $output, $ret);
    DEBUG($output);
    DEBUG($ret);
    if ($ret == 0) {
        exec('/usr/bin/gpg --batch --with-colons --list-keys '.escapeshellarg($pgp_id), $output);
        DEBUG($output);
        foreach ($output as $row) {
            if (substr($row, 0, 4) === 'fpr:') {
                $parts = explode(':', $row);
                // Fingerprint
                return $parts[9];
            }
        }
    }
    return null;
}


function domainlist_by_contact($c)
{
    $cid = (int) $_SESSION['customerinfo']['customerno'];
    $result = db_query("SELECT id FROM kundendaten.domains WHERE (owner=? OR admin_c=?) AND kunde=?", array($c['id'], $c['id'], $cid));
    $ret = array();
    while ($domain = $result->fetch()) {
        $ret[] = new Domain((int) $domain['id']);
    }
    return $ret;
}


function contact_as_string($contact)
{
    $adresse = nl2br("\n".filter_input_general($contact['address'])."\n".filter_input_general($contact['country']).'-'.filter_input_general($contact['zip']).' '.filter_input_general($contact['city']));
    if (! $contact['city']) {
        $adresse = '';
    }
    $name = filter_input_general($contact['name']);
    if ($contact['company']) {
        $name = filter_input_general($contact['company'])."<br />".filter_input_general($contact['name']);
    }
    $email = filter_input_general($contact['email']);
    $new_email = update_pending($contact['id']);
    if ($new_email) {
        $email = "<strike>$email</strike><br/>".filter_input_general($new_email).footnote('Die E-Mail-Adresse wurde noch nicht bestätigt');
    }
    $email = implode("<br>\n", array_filter(array($email, filter_input_general($contact['phone']), filter_input_general($contact['fax']), filter_input_general($contact['mobile']))));
    $pgp = '';
    if ($contact['pgp_id']) {
        $pgpid = $contact['pgp_id'];
        if (strlen($pgpid) > 20) {
            $pgpid = substr($pgpid, 0, 20).' '.substr($pgpid, 20);
        }
        $pgp = '<p class="contact-pgp">'.other_icon('key.png').' PGP ID:<br>'.$pgpid.'</p>';
    }

    $contact_string = "<p class=\"contact-id\">#{$contact['id']}</p><p class=\"contact-address\"><strong>$name</strong>$adresse</p><p class=\"contact-contact\">$email</p>$pgp";
    return $contact_string;
}

function display_contact($contact, $additional_html='', $cssclass='')
{
    $html = contact_as_string($contact);
    $contact_string = "<div class=\"contact {$cssclass}\" id=\"contact-{$contact['id']}\">{$html}{$additional_html}</div>";
    return $contact_string;
}

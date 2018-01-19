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
require_role(array(ROLE_CUSTOMER));

require_once('api.php');

/*
Todo:
    - "separate Rechnungsadresse einrichten" / "Zusätzliche Adresse für den Notfall"
    - Domaininhaber festlegen    
*/


function new_contact() {
    return array("id" => NULL,
        "state" => NULL,
        "lastchange" => time(),
        "nic_handle" => NULL,
        "nic_id" => NULL,
        "company" => NULL,
        "name" => NULL,
        "address" => NULL,
        "zip" => NULL,
        "city" => NULL,
        "country" => "DE",
        "phone" => NULL,
        "mobile" => NULL,
        "fax" => NULL,
        "email" => NULL,
        "pgp_id" => NULL,
        "pgp_key" => NULL,
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

function get_contacts() {
    $cid = (int) $_SESSION['customerinfo']['customerno'];
    $result = db_query("SELECT id, state, lastchange, nic_id, nic_handle, company, name, address, zip, city, country, phone, mobile, fax, email, pgp_id, pgp_key FROM kundendaten.contacts WHERE (state<>'deleted' OR state IS NULL) AND customer=? ORDER BY id", array($cid));
    $ret = array();
    while ($contact = $result->fetch()) {
        $ret[$contact['id']] = $contact;
    }
    DEBUG($ret);
    return $ret;
}


function is_domainholder($contactid) {
    $contactid = (int) $contactid;
    $result = db_query("SELECT id FROM kundendaten.domains WHERE owner=? OR admin_c=?", array($contactid, $contactid));
    if ($result->rowCount() > 0) {
        return true;
    }
    return false;
}

function possible_domainholders() {
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


function get_kundenkontakte() {
    $cid = (int) $_SESSION['customerinfo']['customerno'];
    $result = db_query("SELECT contact_kunde, contact_extern, contact_rechnung FROM kundendaten.kunden WHERE id=?", array($cid));
    $res = $result->fetch();
    $ret = array("kunde" => $res['contact_kunde'],
                 "extern" => $res['contact_extern'],
                 "rechnung" => $res['contact_rechnung']);
    return $ret;
}

function save_emailaddress($id, $email) 
{
    // Speichert eine E-Mail-Adresse direkt, z.B. wenn diese schonmal geprüt wurde
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

    db_query("INSERT INTO kundendaten.mailaddress_token (token, expire, contact, email) VALUES (:token, NOW() + INTERVAL 1 DAY, :id, :email)" , $args);
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

function update_pending($contactid) {
    $contactid = (int) $contactid;
    $result = db_query("SELECT email FROM kundendaten.mailaddress_token WHERE contact=?", array($contactid));
    if ($result->rowCount() == 0) {
        return NULL;
    }
    $res = $result->fetch();
    return $res['email'];
}



function delete_contact($id) {
    $c = get_contact($id);
    if ($c['nic_id']) {
        // Lösche bei der Registry
        $c['state'] = 'deleted';
        upload_contact($c);
    }
    db_query("UPDATE kundendaten.contacts SET state='deleted' WHERE id=?", array($c['id']));
}


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

require_once("inc/api.php");

function verify_mail_token($token)
{
  db_query("DELETE FROM kundendaten.mailaddress_token WHERE expire<NOW()");
  $args = array(":token" => $token);
  $result = db_query("SELECT contact, email FROM kundendaten.mailaddress_token WHERE token=:token AND expire>NOW()", $args);
  if ($result->rowCount() > 0)
  {
    $line = $result->fetch();
    db_query("DELETE FROM kundendaten.mailaddress_token WHERE token=:token", $args); 
    return $line;
  } else {
    return NULL;
  }
}


function update_mailaddress($daten)
{
    $contact = $daten['contact'];
    $email = $daten['email'];

    if (! check_emailaddr($email)) {
        system_failure('Es ist eine ungültige Adresse hinterlegt. So wird das nichts. Bitte die Änderung von vorne machen.');
    } 

    $args = array(':contact' => $contact,
                  ':email' => $email);
    db_query("UPDATE kundendaten.contacts SET email=:email WHERE id=:contact", $args);
    
}


function upload_changed_contact($id)
{
    $args = array(
        "id" => (int) $id);
    $result = db_query("SELECT id, state, lastchange, nic_id, nic_handle, company, name, address, zip, city, country, phone, mobile, fax, email, pgp_id, pgp_key FROM kundendaten.contacts WHERE id=:id", $args);
    if ($result->rowCount() == 0) {
        return ;
    }
    $c = $result->fetch();
    if (! ($c['nic_id'] || $c['nic_handle'])) {
        return ;
    }
    $ac = array();
    $ac['id'] = $c['nic_id'];
    $ac['handle'] = $c['nic_handle'];
    $ac['type'] = 'person';
    $ac['name'] = $c['name'];
    $ac['organization'] = $c['company'];
    $ac['street'] = explode("\n", $c['address'], 3);
    $ac['postalCode'] = $c['zip'];
    $ac['city'] = $c['city'];
    $ac['country'] = strtolower($c['country']);
    $ac['emailAddress'] = $c['email'];
    $ac['phoneNumber'] = $c['phone'];
    $ac['faxNumber'] = $c['fax'];
    if ($c['state'] == 'deleted') {
        $ac['hidden'] = true;
    }
    $data = array("contact" => $ac,
            "actingAs" => "designatedAgent");
    $result = api_request('contactUpdate', $data);
    if ($result['status'] != 'success') {
            warning("Es gab ein Problem beim Hochladen der geänderten Adresse zum Domainregistrar. Das sollte nicht sein!");
    }
}



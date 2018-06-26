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

require_role(ROLE_CUSTOMER);


function get_orderprocessing_contract()
{
    $args = array(
        "cid" => (int) $_SESSION['customerinfo']['customerno']);
    $result = db_query("SELECT id, signed, type, startdate, enddate FROM kundendaten.contract WHERE customer=:cid AND type='orderprocessing' AND (enddate IS NULL OR enddate < CURDATE())", $args);
    if ($result->rowCount() == 0) {
        return null;
    }
    $line = $result->fetch();
    return $line;
}


function contract_html()
{
    use_module('contacts');
    require_once('contacts.php');

    $kundenkontakte = get_kundenkontakte();
    $kunde = get_contact($kundenkontakte['kunde']);
    $adresse = nl2br("\n".filter_input_general($kunde['address'])."\n".filter_input_general($kunde['country']).'-'.filter_input_general($kunde['zip']).' '.filter_input_general($kunde['city']));
    $name = filter_input_general($kunde['name']);
    if ($kunde['company']) {
        $name = filter_input_general($kunde['company'])."<br />".filter_input_general($kunde['name']);
    }
    $email = filter_input_general($kunde['email']);
    $address = "<strong>$name</strong>$adresse</p><p>E-Mail-Adresse: $email";

    $date = date('d.m.Y');

    $DIR=realpath(dirname(__FILE__).'/..');

    $vertrag = file_get_contents($DIR.'/vertrag.html');
    $vertrag = str_replace('((ADRESSE))', $address, $vertrag);
    $vertrag = str_replace('((DATUM))', $date, $vertrag);

    $vertrag = str_replace('</body>', '', $vertrag);
    $vertrag = str_replace('</html>', '', $vertrag);

    return $vertrag."<br><br><pagebreak>\n".file_get_contents($DIR.'/anlage1.html')."<br><br><pagebreak>\n".file_get_contents($DIR.'/anlage2.html')."</body></html>";
}


function save_op_contract($pdfdata)
{
    $args = array("cid" => $_SESSION['customerinfo']['customerno'],
            "pdfdata" => $pdfdata);
    db_query(
        "INSERT INTO kundendaten.contract (customer, signed, type, startdate, pdfdata) VALUES (:cid, NOW(), 'orderprocessing', CURDATE(), :pdfdata)",
        $args
    );
}


function get_contract_pdf($id)
{
    $args = array("id" => $id,
        "cid" => $_SESSION['customerinfo']['customerno']);
    $result = db_query("SELECT pdfdata FROM kundendaten.contract WHERE id=:id AND customer=:cid", $args);
    $line = $result->fetch();
    return $line['pdfdata'];
}

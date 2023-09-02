<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_role(ROLE_CUSTOMER);


function get_orderprocessing_contract()
{
    $args = [
        "cid" => (int) $_SESSION['customerinfo']['customerno'], ];
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
    $adresse = nl2br("\n".filter_output_html($kunde['address']."\n".$kunde['country'].'-'.$kunde['zip'].' '.$kunde['city']));
    $name = filter_output_html($kunde['name']);
    if ($kunde['company']) {
        $name = filter_output_html($kunde['company'])."<br />".filter_output_html($kunde['name']);
    }
    $email = filter_output_html($kunde['email']);
    $address = "<strong>$name</strong>$adresse</p><p>E-Mail-Adresse: $email";

    $date = date('d.m.Y');

    $DIR = realpath(dirname(__FILE__).'/..');

    $vertrag = file_get_contents($DIR.'/vertrag.html');
    $vertrag = str_replace('((ADRESSE))', $address, $vertrag);
    $vertrag = str_replace('((DATUM))', $date, $vertrag);

    $vertrag = str_replace('</body>', '', $vertrag);
    $vertrag = str_replace('</html>', '', $vertrag);

    return $vertrag."<br><br><pagebreak>\n".file_get_contents($DIR.'/anlage1.html')."<br><br><pagebreak>\n".file_get_contents($DIR.'/anlage2.html')."</body></html>";
}


function save_op_contract($pdfdata)
{
    $args = ["cid" => $_SESSION['customerinfo']['customerno'],
            "pdfdata" => $pdfdata, ];
    db_query(
        "INSERT INTO kundendaten.contract (customer, signed, type, startdate, pdfdata) VALUES (:cid, NOW(), 'orderprocessing', CURDATE(), :pdfdata)",
        $args
    );
}


function get_contract_pdf($id)
{
    $args = ["id" => $id,
        "cid" => $_SESSION['customerinfo']['customerno'], ];
    $result = db_query("SELECT pdfdata FROM kundendaten.contract WHERE id=:id AND customer=:cid", $args);
    $line = $result->fetch();
    return $line['pdfdata'];
}

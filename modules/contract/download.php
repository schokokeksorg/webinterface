<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('contract.php');
require_role(ROLE_CUSTOMER);

$pdfdata = get_contract_pdf($_REQUEST['id']);
if (!$pdfdata) {
    system_failure('Die PDF-Version dieses Vertrags konnte nicht ausgelesen werden. Bitte wenden Sie sich an den Support.');
} else {
    $filename = 'av_vertrag.pdf';
    header('Content-type: application/pdf');
    header('Content-disposition: attachment; filename=' . $filename);
    echo $pdfdata;
    die();
}

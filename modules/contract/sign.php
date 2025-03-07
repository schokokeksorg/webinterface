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

require_role([ROLE_CUSTOMER]);

if (isset($_REQUEST['agree']) && $_REQUEST['agree'] == 'yes') {
    require_once('vendor/autoload.php');

    $mpdf = new \Mpdf\Mpdf();
    $mpdf->WriteHTML(contract_html());
    $pdfdata = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);

    save_op_contract($pdfdata);

    redirect('list');
} else {
    system_failure("Sie müssen dem Vertrag zustimmen!");
}

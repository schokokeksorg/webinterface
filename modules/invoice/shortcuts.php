<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

if ($_SESSION['role'] & ROLE_CUSTOMER) {
    $alert = null;
    include_once('modules/invoice/include/invoice.php');
    $unpayed_invoices = 0;
    $my_invoices = my_invoices();
    foreach ($my_invoices as $inv) {
        if ($inv['bezahlt'] == 0) {
            $l = get_lastschrift($inv['id']);
            if (! $l || $l['status'] == 'rejected') {
                $unpayed_invoices++;
            }
        }
    }
    if ($unpayed_invoices > 0) {
        $alert = $unpayed_invoices.' unbezahlt';
    }

    $shortcuts[] = [ 'section' => 'administration',
                        'weight'  => 50,
                        'file'    => 'current',
                        'icon'    => 'invoice.png',
                        'title'   => 'Ihre Rechnungen',
                        'alert'   => $alert, ];
}

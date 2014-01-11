<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2013 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

if ($_SESSION['role'] & ROLE_CUSTOMER) {
  $alert = NULL;
  include_once('modules/invoice/include/invoice.php');
  $unpayed_invoices = 0;
  $my_invoices = my_invoices();
  foreach($my_invoices AS $inv) {
    if ($inv['bezahlt'] == 0) {
      $l = get_lastschrift($inv['id']);
      if (! $l) {
        $unpayed_invoices++;
      }
    }
  }
  if ($unpayed_invoices > 0) {
    $alert = $unpayed_invoices.' unbezahlt';
  }
  
  $shortcuts[] = array( 'section' => 'administration', 
                        'weight'  => 50, 
                        'file'    => 'current', 
                        'icon'    => 'invoice.png', 
                        'title'   => 'Ihre Rechnungen',
                        'alert'   => $alert );
}

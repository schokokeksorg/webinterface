<?php

if ($_SESSION['role'] & ROLE_CUSTOMER) {
  $alert = NULL;
  include_once('modules/invoice/include/invoice.php');
  $unpayed_invoices = 0;
  $my_invoices = my_invoices();
  foreach($my_invoices AS $inv) {
    if ($inv['bezahlt'] == 0)
      $unpayed_invoices++;
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

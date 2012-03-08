<?php

if ($_SESSION['role'] & ROLE_SYSTEMUSER) {
  $shortcuts[] = array( 'section' => 'Webserver', 
                        'weight'  => 30, 
                        'file'    => 'vhosts', 
                        'icon'    => 'webserver.png', 
                        'title'   => 'Webserver-Einstellungen',
                        'alert'   => NULL );
}
if ($_SESSION['role'] & ROLE_SYSTEMUSER) {
  $alert = '';
  include_once('modules/vhosts/include/certs.php');
  $certs = user_certs();
  if (count($certs) > 0)
  {
    $num_expired = 0;
    $num_warn = 0;
    foreach ($certs as $c)
    {
      if ($c['valid_until'] <= date('Y-m-d')) {
        $num_expired++;
      } elseif ($c['valid_until'] <= date('Y-m-d', time()+(30*24*3600))) {
        $num_warn++;
      }
    }
    if ($num_expired > 0) {
      $alert .= 'Zertifikate abgelaufen';
    } elseif ($num_warn > 0) {
      $alert .= 'Zertifikate bald abgelaufen';
    }
  }
 
  if ($alert) {
    $shortcuts[] = array( 'section' => 'Webserver', 
                          'weight'  => 80, 
                          'file'    => 'certs', 
                          'icon'    => 'secure.png', 
                          'title'   => 'SSL-Zertifikate',
                          'alert'   => $alert );
  }
}

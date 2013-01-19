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

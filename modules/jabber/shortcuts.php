<?php

if ($_SESSION['role'] & ROLE_CUSTOMER) {
  $shortcuts[] = array( 'section' => 'Jabber', 
                        'weight'  => 10, 
                        'file'    => 'accounts', 
                        'icon'    => 'jabber.png', 
                        'title'   => 'Jabber-Accounts',
                        'alert'   => NULL );
}

<?php

if ($_SESSION['role'] & ROLE_CUSTOMER || $_SESSION['role'] & ROLE_SYSTEMUSER) {
  $shortcuts[] = array( 'section' => 'administration', 
                        'weight'  => 90, 
                        'file'    => 'chpass', 
                        'icon'    => 'pwchange.png', 
                        'title'   => 'Passwort ändern',
                        'alert'   => NULL );
}

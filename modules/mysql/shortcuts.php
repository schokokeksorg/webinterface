<?php

if ($_SESSION['role'] & ROLE_SYSTEMUSER) {
  $shortcuts[] = array( 'section' => 'Datenbank', 
                        'weight'  => 20, 
                        'file'    => 'databases', 
                        'icon'    => 'mysql.png', 
                        'title'   => 'MySQL-Datenbanken',
                        'alert'   => NULL );
}

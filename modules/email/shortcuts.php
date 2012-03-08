<?php

if ($_SESSION['role'] & ROLE_SYSTEMUSER) {
  $shortcuts[] = array( 'section' => 'E-Mail', 
                        'weight'  => 40, 
                        'file'    => 'vmail', 
                        'icon'    => 'email.png', 
                        'title'   => 'E-Mail-Adressen verwalten',
                        'alert'   => NULL );
}
if ($_SESSION['role'] & ROLE_MAILACCOUNT || $_SESSION['role'] & ROLE_VMAIL_ACCOUNT) {
  $shortcuts[] = array( 'section' => 'E-Mail', 
                        'weight'  => 50, 
                        'file'    => 'chpass', 
                        'icon'    => 'pwchange.png', 
                        'title'   => 'Passwort ändern',
                        'alert'   => NULL );
}
if ($_SESSION['role'] & ROLE_VMAIL_ACCOUNT) {
  $shortcuts[] = array( 'section' => 'E-Mail', 
                        'weight'  => 40, 
                        'file'    => 'edit', 
                        'icon'    => 'cog.png', 
                        'title'   => 'E-Mail-Einstellungen',
                        'alert'   => NULL );
}

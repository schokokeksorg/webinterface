<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

if ($_SESSION['role'] & ROLE_SYSTEMUSER) {
    $shortcuts[] = [ 'section' => 'E-Mail',
                        'weight'  => 40,
                        'file'    => 'vmail',
                        'icon'    => 'email.png',
                        'title'   => 'E-Mail-Adressen verwalten',
                        'alert'   => null, ];
}
if ($_SESSION['role'] & ROLE_MAILACCOUNT || $_SESSION['role'] & ROLE_VMAIL_ACCOUNT) {
    $shortcuts[] = [ 'section' => 'E-Mail',
                        'weight'  => 50,
                        'file'    => 'chpass',
                        'icon'    => 'pwchange.png',
                        'title'   => 'Passwort ändern',
                        'alert'   => null, ];
}
if ($_SESSION['role'] & ROLE_VMAIL_ACCOUNT) {
    $shortcuts[] = [ 'section' => 'E-Mail',
                        'weight'  => 40,
                        'file'    => 'edit',
                        'icon'    => 'cog.png',
                        'title'   => 'E-Mail-Einstellungen',
                        'alert'   => null, ];
}

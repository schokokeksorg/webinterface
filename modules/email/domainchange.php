<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2018 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('session/start.php');
require_once('vmail.php');

require_once("inc/debug.php");
global $debugmode;

require_role(ROLE_SYSTEMUSER);

check_form_token('vmail_domainchange');

foreach ($_POST as $key => $value) {
  if (strpos($key, "option-") === 0) {
    $id = substr($key, 7);
    $type = 'virtual';
    if ($value == 'manual') {
      $type = 'auto';
    } elseif ($value == 'off') {
      $type = 'none';
    }
    DEBUG('change request for id #'.$id.' to '.$value);
    change_domain($id, $type);
  }
}

if (!$debugmode) {
  header('Location: domains');
  die();
}



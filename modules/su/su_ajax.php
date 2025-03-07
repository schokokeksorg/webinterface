<?php

/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/base.php');
require_once('inc/security.php');
require_once('inc/debug.php');

require_once('session/start.php');
require_once('su.php');


require_role(ROLE_SYSADMIN);

# Save the timestamp of this request to the session, so we accept only actions performed some seconds after this
$_SESSION['su_ajax_timestamp'] = time();

$term = $_GET['term'];

$allentries = build_results($term);

$out = [];

foreach ($allentries as $entry) {
    $out[] = ['id' => $entry['id'], 'value' => $entry['value']];
}


header("Content-Type: application/json");
echo json_encode($out);
die();

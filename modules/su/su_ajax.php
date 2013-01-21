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

require_once('inc/base.php');
require_once('inc/debug.php');

require_once('session/start.php');
require_once('su.php');

require_once('class/customer.php');

require_role(ROLE_SYSADMIN);

# Save the timestamp of this request to the session, so we accept only actions performed some seconds after this
$_SESSION['su_ajax_timestamp'] = time();

$term = $_GET['term'];
$ret = array();

function add($val, $id, $value) {
  global $ret;
  if (isset($ret[$val]) && is_array($ret[$val])) {
    array_push($ret[$val], array("id" => $id, "value" => $value));
  } else {
    $ret[$val] = array( array("id" => $id, "value" => $value) );
  }
}


$result = array_unique(find_customers($term));
sort($result);
foreach ($result as $val) {
  $c = new Customer((int) $val);
  if ($c->id == $term) {
    add(10, "c{$c->id}", "Kunde {$c->id}: {$c->fullname}");
  } else {
    add(90, "c{$c->id}", "Kunde {$c->id}: {$c->fullname}");
  }
  $users = find_users_for_customer($c->id);
  foreach ($users as $uid => $username) {
    if ($uid == $term || $username == $term) {
      add(15, "u{$uid}", "User {$uid}: {$username}");
    } elseif (strstr($username, $term)) {
      add(20, "u{$uid}", "User {$uid}: {$username}");
    } else {
      add(85, "u{$uid}", "User {$uid}: {$username}");
    }
  }
}

ksort($ret);

$lines = array();
foreach ($ret as $group) {
  foreach ($group as $entry) {
    $lines[] = "  { \"id\": \"{$entry['id']}\", \"value\": \"{$entry['value']}\" }";
  }
}



header("Content-Type: text/javascript");
echo "[\n";
echo implode(",\n", $lines);
echo '
]';
die();



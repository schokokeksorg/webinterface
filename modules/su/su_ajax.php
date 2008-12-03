<?php

require_once('inc/base.php');
require_once('inc/debug.php');

require_once('session/start.php');
require_once('su.php');

require_once('class/customer.php');

require_role(ROLE_SYSADMIN);

$ajax_formtoken = generate_form_token('su_su_ajax');

$result = array_unique(find_customers($_GET['q']));
sort($result);
foreach ($result as $val) {
  $c = new Customer((int) $val);
  echo '<div style="margin-bottom: 0.5em;">'.internal_link('su.php', 'Kunde '.$c->id.': <strong>'.$c->fullname.'</strong>', 'type=customer&id='.$c->id.'&formtoken='.$ajax_formtoken);
  $users = find_users_for_customer($c->id);
  foreach ($users as $uid => $username) {
    echo '<p style="padding:0; margin:0;margin-left: 2em;">'.internal_link('', 'User »'.$username.'« (UID '.$uid.')', 'type=systemuser&uid='.$uid.'&formtoken='.$ajax_formtoken).'</p>';
  }
  echo '</div>';
}
die();



<?php

require_once('inc/base.php');
require_once('inc/debug.php');

require_once('session/start.php');
require_once('crm.php');

require_once('class/customer.php');

require_role(ROLE_SYSADMIN);

$ajax_formtoken = generate_form_token('crm_crm_ajax');

$result = array_unique(find_customers($_GET['q']));
sort($result);
foreach ($result as $val) {
  $c = new Customer((int) $val);
  echo '<p style="margin-bottom: 0.5em;">'.internal_link('select_customer', 'Kunde '.$c->id.': <strong>'.$c->fullname.'</strong>', 'customer='.$c->id.'&formtoken='.$ajax_formtoken);
  echo '</p>';
}
die();



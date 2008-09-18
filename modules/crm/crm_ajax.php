<?php

require_once('inc/base.php');
require_once('inc/debug.php');

require_once('session/start.php');
require_once('crm.php');

require_role(ROLE_SYSADMIN);

print_r($_GET);

$result = array_unique(find_customer($_GET['q']));
sort($result);
foreach ($result as $val) {
  echo '<p>Kundennummer: <strong>'.$val.'</strong></p>';
}
die();



<?php

require_once('session/start.php');
require_once('vmail.php');

require_once("inc/debug.php");
global $debugmode;

require_role(ROLE_SYSTEMUSER);

check_form_token('vmail_domainchange');

if (! $_POST['type'] || ! $_POST['id'])
  system_failure("Unvollständige POST-Daten");

change_domain($_POST['id'], $_POST['type']);

if (!$debugmode) {
  header('Location: domains.php');
  die();
}



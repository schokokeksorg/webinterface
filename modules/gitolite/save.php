<?php
require_role(ROLE_SYSTEMUSER);

include('git.php');

if ($_GET['action'] == 'newuser') {
  $handle = $_POST['handle'];
  if ($handle == '') {
    system_failure("Leere Benutzerbezeichnung!");
  }
  $users = list_users();
  if (in_array($handle, $users)) {
    system_failure("Ein Benutzer mit diesem Namen existiert bereits.");
  }
  newkey($_POST['pubkey'], $handle);
  if (! $debugmode)
    header('Location: git');
  die();
} elseif ($_GET['action'] == 'newkey') {
  $handle = $_POST['handle'];
  if ($handle == '') {
    system_failure("Leere Benutzerbezeichnung!");
  }
  newkey($_POST['pubkey'], $handle);
  if (! $debugmode)
    header('Location: git');
  die();
}




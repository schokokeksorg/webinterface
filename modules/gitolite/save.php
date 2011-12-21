<?php
require_role(ROLE_SYSTEMUSER);

include('git.php');

if ($_GET['action'] == 'newuser') {
  check_form_token('git_newkey');
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
  check_form_token('git_newkey');
  $handle = $_POST['handle'];
  if ($handle == '') {
    system_failure("Leere Benutzerbezeichnung!");
  }
  newkey($_POST['pubkey'], $handle);
  if (! $debugmode)
    header('Location: git');
  die();
} elseif ($_GET['action'] == 'newrepo' || $_GET['action'] == 'editrepo') {
  check_form_token('git_edit');
  $permissions = array();
  $users = list_users();
  foreach ($users as $u) {  
    if (isset($_POST[$u])) {
      switch ($_POST[$u]) {
        case 'rwplus': $permissions[$u] = 'RW+';
          break;
        case 'rw': $permissions[$u] = 'RW';
          break;
        case 'r': $permissions[$u] = 'R';
          break;
      }
    }
  }
  if ($_POST['gitweb'] == 'r') {
    $permissions['gitweb'] = 'R';
    $description = $_POST['description'];
  } else {
    $description = NULL;
  }
  save_repo($_POST['repo'], $permissions, $description);
  if (! $debugmode)
    header('Location: git');
  die();
  
}




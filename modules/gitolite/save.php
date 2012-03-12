<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2012 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_role(ROLE_SYSTEMUSER);

$section = 'git_git';
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
} elseif ($_GET['action'] == 'newforeignuser') {
  check_form_token('git_newforeignuser');
  $handle = $_POST['handle'];
  if ($handle == '') {
    system_failure("Leere Benutzerbezeichnung!");
  }
  $users = list_foreign_users();
  if (in_array($handle, $users)) {
    system_failure("Diesen Benutzer haben Sie bereits hinzugefügt.");
  }
  new_foreign_user($handle);
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
  $users = array_merge(list_users(), list_foreign_users());
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
  if (isset($_POST['gitweb']) && ($_POST['gitweb'] == 'r')) {
    $permissions['gitweb'] = 'R';
    $permissions['daemon'] = 'R';
    $description = $_POST['description'];
  } else {
    $description = NULL;
  }
  save_repo($_POST['repo'], $permissions, $description);
  if (! $debugmode)
    header('Location: git');
  die();
  
}




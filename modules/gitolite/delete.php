<?php
require_role(ROLE_SYSTEMUSER);
require_once('inc/security.php');

include('git.php');
$section = 'git_git';

if (isset($_GET['repo'])) {
  $repos = list_repos();
  if (!array_key_exists($_GET['repo'], $repos)) {
    system_failure("Es sollte ein unbekanntes Repository gelöscht werden!");
  }

  $sure = user_is_sure();
  if ($sure === NULL)
  {
    are_you_sure("repo={$_GET['repo']}", '<p>Soll das GIT-Repository »'.$_GET['repo'].'« wirklich gelöscht werden?</p>
    <p>Alle Inhalte die in diesem Repository gespeichert sind, werden gelöscht!</p>');
  }
  elseif ($sure === true)
  {
    delete_repository($_GET['repo']);
    if (! $debugmode)
      header('Location: git');
    die();
  }
  elseif ($sure === false)
  {
    if (! $debugmode)
      header("Location: git");
    die();
  }
}

if (isset($_GET['handle'])) {
  $users = list_users();
  if (!in_array($_GET['handle'], $users)) {
    system_failure("Es sollte ein unbekannter SSH-Key gelöscht werden!");
  }

  $sure = user_is_sure();
  if ($sure === NULL)
  {
    are_you_sure("handle={$_GET['handle']}", '<p>Soll der SSH-Key »'.$_GET['handle'].'« wirklich gelöscht werden?</p>');
  }
  elseif ($sure === true)
  {
    delete_key($_GET['handle']);
    if (! $debugmode)
      header('Location: git');
    die();
  }
  elseif ($sure === false)
  {
    if (! $debugmode)
      header("Location: git");
    die();
  }
}

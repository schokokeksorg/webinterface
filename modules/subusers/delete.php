<?php
require_role(ROLE_SYSTEMUSER);
require_once('inc/security.php');

include('subuser.php');
$section = 'subusers_subusers';

if (isset($_GET['subuser'])) {
  $sure = user_is_sure();
  if ($sure === NULL)
  {
    $subuser = load_subuser($_GET['subuser']);
    are_you_sure("subuser={$subuser['id']}", '
    <p>Soll der zusätzliche Admin-Zugang »'.$subuser['username'].'« wirklich gelöscht werden?</p>');
  }
  elseif ($sure === true)
  {
    delete_subuser($_GET['subuser']);
    if (! $debugmode)
      header('Location: subusers');
    die();
  }
  elseif ($sure === false)
  {
    if (! $debugmode)
      header("Location: subusers");
    die();
  }
}

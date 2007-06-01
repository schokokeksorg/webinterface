<?php

require_once('session/start.php');

require_once('domains.php');
require_once('jabberaccounts.php');

require_role(ROLE_CUSTOMER);

require_once("inc/debug.php");
global $debugmode;
DEBUG("GET: ".htmlentities(print_r($_GET, true))." / POST: ".htmlentities(print_r($_POST, true)));

if ($_GET['action'] == 'new')
{
  check_form_token('jabber_new_account');
  if ($_POST['local'] == '' ||
      $_POST['domain'] == '' ||
      $_POST['password'] == '')
  {
    input_error('Sie müssen alle Felder ausfüllen!');
  }
  else
  {
    create_jabber_account($_POST['local'], $_POST['domain'], $_POST['password']);
    if (! $debugmode)
      header('Location: accounts.php');
  }
}
elseif ($_GET['action'] == 'chpass')
{
  check_form_token('jabber_chpass');
  if ($_POST['newpass'] == '' ||
      $_POST['newpass2'] == '' ||
      $_POST['newpass'] != $_POST['newpass2'] ||
      $_POST['accountid'] == '')
  {
    input_error('Bitte zweimal ein neues Passwort eingeben!');
  }
  else
  {
    change_jabber_password($_POST['accountid'], $_POST['newpass']);
    if (! $debugmode)
      header('Location: accounts.php');
  }
}
elseif ($_GET['action'] == 'delete')
{
  $account = get_jabberaccount_details($_GET['account']);
  $account_string = $account['local'].'@'.$account['domain'];
  $sure = user_is_sure();
  if ($sure === NULL)
  {
    are_you_sure("action=delete&amp;account={$_GET['account']}", "Möchten Sie den Account »{$account_string}« wirklich löschen?");
  }
  elseif ($sure === true)
  {
    delete_jabber_account($account['id']);
    if (! $debugmode)
      header("Location: accounts.php");
  }
  elseif ($sure === false)
  {
    if (! $debugmode)
      header("Location: accounts.php");
  }

}
else
  system_failure("Unimplemented action");

output('');


?>

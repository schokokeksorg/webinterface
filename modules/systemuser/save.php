<?php

require_once('session/start.php');

require_once('useraccounts.php');

require_once('inc/security.php');


require_role(ROLE_CUSTOMER);

require_once("inc/debug.php");
global $debugmode;

if ($_GET['action'] == 'new')
{
  system_failure('not implemented');
  /*
  check_form_token('systemuser_new');
  if (filter_input_username($_POST['username']) == '' ||
      filter_shell($_POST['password']) == '')
  {
    input_error('Sie müssen alle Felder ausfüllen!');
  }
  else
  {
    create_jabber_account($_POST['local'], $_POST['domain'], $_POST['password']);
    if (! $debugmode)
      header('Location: accounts.php');
  }
  */
}
elseif ($_GET['action'] == 'edit')
{
  $error = false;
  check_form_token('systemuser_edit');
  if (customer_useraccount($_POST['uid']))
    system_failure('Aus Sicherheitsgründen können Sie diesen Account nicht ändern!');

  if ($_POST['newpass'] != '')
  {
    //if (! strong_password($_POST['newpass']))
    //  input_error('Das Passwort ist zu einfach');
    //else
    if ($_POST['newpass2'] == '' ||
        $_POST['newpass'] != $_POST['newpass2'])
    {
      input_error('Bitte zweimal ein neues Passwort eingeben!');
      $error = true;
    }
    else
    {
      $user = get_account_details($_POST['uid']);
      # set_systemuser_password kommt aus den Session-Funktionen!
      set_systemuser_password($user['uid'], $_POST['newpass']);
    }
  }

  set_systemuser_details($_POST['uid'], $_POST['fullname'], $_POST['quota']);
  if (! ($debugmode || $error))
    header('Location: accounts.php');
  
}
elseif ($_GET['action'] == 'delete')
{
  system_failure("Benutzeraccounts zu löschen ist momentan nicht über diese Oberfläche möglich. Bitte wenden Sie sich an einen Administrator.");
  /*
  $account_string = filter_input_general( $account['local'].'@'.$account['domain'] );
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
  */
}
else
  system_failure("Unimplemented action");

output('');


?>

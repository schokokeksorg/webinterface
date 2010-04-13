<?php

require_once('session/start.php');

require_once('useraccounts.php');

require_once('inc/security.php');


require_role(array(ROLE_CUSTOMER, ROLE_SYSTEMUSER));

$role = $_SESSION['role'];

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
      header('Location: accounts');
  }
  */
}
elseif ($_GET['action'] == 'pwchange')
{
  if (! $role & ROLE_CUSTOMER)
    system_failure("Zum Ändern Ihres Passworts verwenden Sie bitte die Funktion im Hauptmenü!");
  $error = false;
  check_form_token('systemuser_pwchange');
  if (customer_useraccount($_REQUEST['uid']))
    system_failure('Zum Ändern dieses Passworts verwenden Sie bitte die Funktion im Hauptmenü!');

  //if (! strong_password($_POST['newpass']))
  //  input_error('Das Passwort ist zu einfach');
  //else
  if ($_POST['newpass1'] == '' ||
      $_POST['newpass1'] != $_POST['newpass2'])
  {
    input_error('Bitte zweimal ein neues Passwort eingeben!');
    $error = true;
  }
  else
  {
    $user = get_account_details($_REQUEST['uid']);
    # set_systemuser_password kommt aus den Session-Funktionen!
    set_systemuser_password($user['uid'], $_POST['newpass1']);
  }
  if (! ($debugmode || $error))
    header('Location: accounts');
}
elseif ($_GET['action'] == 'edit')
{
  check_form_token('systemuser_edit');
  $account = NULL;
  if ($role & ROLE_CUSTOMER)
    $account = get_account_details($_REQUEST['uid']);
  else
    $account = get_account_details($_SESSION['userinfo']['uid'], $_SESSION['userinfo']['customerno']);

  if ($role & ROLE_CUSTOMER)
  {
    $customerquota = get_customer_quota();
    $maxquota = $customerquota['max'] - $customerquota['assigned'] + $account['quota'];
   
    $quota = (int) $_POST['quota'];
    if ($quota > $maxquota) 
      system_failure("Sie können diesem Account maximal {$maxquota} MB Speicherplatz zuweisen.");
    $account['quota'] = $quota;
  }

  if ($_POST['defaultname'] == 1)
    $account['name'] = NULL;
  else
    $account['name'] = filter_input_general($_POST['fullname']);
  
  $shells = available_shells();
  if (isset($shells[$_POST['shell']]))
    $account['shell'] = $_POST['shell'];

  set_account_details($account);
  $target = 'accounts';
  if (! ($role & ROLE_CUSTOMER))
    $target = 'myaccount';
  if (! ($debugmode || $error))
    header('Location: '.$target);
  
}
elseif ($_GET['action'] == 'delete')
{
  system_failure("Benutzeraccounts zu löschen ist momentan nicht über diese Oberfläche möglich. Bitte wenden Sie sich an einen Administrator.");
  /*
  $account_string = filter_input_general( $account['local'].'@'.$account['domain'] );
  $sure = user_is_sure();
  if ($sure === NULL)
  {
    are_you_sure("action=delete&account={$_GET['account']}", "Möchten Sie den Account »{$account_string}« wirklich löschen?");
  }
  elseif ($sure === true)
  {
    delete_jabber_account($account['id']);
    if (! $debugmode)
      header("Location: accounts");
  }
  elseif ($sure === false)
  {
    if (! $debugmode)
      header("Location: accounts");
  }
  */
}
else
  system_failure("Unimplemented action");

output('');


?>

<?php

require_once('session/start.php');

require_once('vmail.php');

require_role(ROLE_SYSTEMUSER);

require_once("inc/debug.php");
global $debugmode;


if ($_GET['action'] == 'edit')
{
  check_form_token('vmail_edit_mailbox');
  $id = (int) $_GET['id'];

  $account = empty_account();
  $account['id'] = NULL;
  if ($id)
    $account['id'] = $id;
  $account['local'] = $_POST['local'];
  $account['domain'] = (int) $_POST['domain'];
  $account['spamfilter'] = $_POST['spamfilter_action'];
  $account['password'] = $_POST['password'];
  if (($account['password'] == '') && ($_POST['mailbox'] == 'yes'))
    system_failure("Sie haben ein leeres Passwort eingegeben!");
  if ($_POST['password'] == '**********')
    $account['password'] = '';
  if ($_POST['mailbox'] != 'yes')
    $account['password'] = NULL;

  if ($_POST['forward'] == 'yes')
  {
    $num = 1;
    while (true)
    {
      if (! isset($_POST['forward_to_'.$num]))
        break;
      if ($_POST['forward_to_'.$num] == '')
        break;
      $fwd = array("spamfilter" => $_POST['spamfilter_action_'.$num], "destination" => $_POST['forward_to_'.$num]);
      array_push($account['forwards'], $fwd);
      $num++;
    }
    if ($num == 1) system_failure("Bitte mindestens eine Weiterleitungsadresse angeben.");
  }

  if (($_POST['forward']!='yes') && ($_POST['mailbox']!='yes'))
    system_failure("Entweder eine Mailbox oder eine Weiterleitung muss angegeben werden!");

  DEBUG($account);

  save_vmail_account($account);

  if (! ($debugmode || we_have_an_error()))
    header('Location: accounts.php');
}
elseif ($_GET['action'] == 'delete')
{
  $title = "E-mail-Adresse löschen";
  $section = 'vmail_vmail';

  $account = get_account_details( (int) $_GET['id'] );

  $domain = NULL;
  $domains = get_vmail_domains();
  foreach ($domains as $dom)
    if ($dom->id == $account['domain'])
    {
      $domain = $dom->domainname;
      break;
    }
  $account_string = $account['local'] . "@" . $domain;
  $sure = user_is_sure();
  if ($sure === NULL)
  {
    are_you_sure("action=delete&amp;id={$account['id']}", "Möchten Sie die E-Mail-Adresse »{$account_string}« wirklich löschen?");
  }
  elseif ($sure === true)
  {
    delete_account($account['id']);
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

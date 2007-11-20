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
  $account['type'] = $_POST['type'];
  if ($_POST['type'] == 'mailbox')
    $account['data'] = $_POST['password'];
  else
    $account['data'] = $_POST['forward_to'];
  $account['spamfilter'] = $_POST['spamfilter_action'];
  if ($_POST['spamfilter'] != '1')
    $account['spamfilter'] = NULL;
  $account['virusfilter'] = $_POST['virusfilter_action'];
  if ($_POST['virusfilter'] != '1')
    $account['virusfilter'] = NULL;

  DEBUG($account);

  save_vmail_account($account);

  if (! ($debugmode || we_have_an_error()))
    header('Location: accounts.php');

}
else
  system_failure("Unimplemented action");

output('');


?>

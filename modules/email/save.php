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
  {
    $account['password'] = NULL;
    $account['spamfilter'] = 'none';
  }
  if (isset($_POST['quota'])) {
    $account['quota'] = $_POST['quota'];
  }

  $account['quota_threshold'] = -1;
  if (isset($_POST['quota_notify']) && isset($_POST['quota_threshold']) && $_POST['quota_notify'] == 1) {
    $account['quota_threshold'] = $_POST['quota_threshold'];
  }



  $ar = empty_autoresponder_config();
  $valid_from_date = time();
  $valid_until_date = NULL;
  if (isset($_POST['ar_valid_from_day']) && isset($_POST['ar_valid_from_month']) && isset($_POST['ar_valid_from_year'])) {
    $valid_from_date = strtotime($_POST['ar_valid_from_year'].'-'.$_POST['ar_valid_from_month'].'-'.$_POST['ar_valid_from_day']);
  }
  if (isset($_POST['ar_valid_until_day']) && isset($_POST['ar_valid_until_month']) && isset($_POST['ar_valid_until_year'])) {
    $valid_until_date = strtotime($_POST['ar_valid_until_year'].'-'.$_POST['ar_valid_until_month'].'-'.$_POST['ar_valid_until_day']);
  }
  if (isset($_POST['ar_valid_from']) && ($_POST['ar_valid_from'] == 'now' || $valid_from_date < time())) {
    $valid_from_date = time();
  }
  $ar['valid_from'] = date('Y-m-d', $valid_from_date);
  $ar['valid_until'] = date('Y-m-d', $valid_until_date);
  if (!isset($_POST['autoresponder']) || $_POST['autoresponder'] != 'yes') {
    $ar['valid_from'] = NULL;
  }
  if (isset($_POST['ar_valid_until']) && ($_POST['ar_valid_until'] == 'infinity' || $valid_until_date < time())) {
    $ar['valid_until'] = NULL;
  }

  if (isset($_POST['ar_subject']) && $_POST['ar_subject'] == 'custom' && isset($_POST['ar_subject_value']) && chop($_POST['ar_subject_value']) != '') {
    $ar['subject'] = filter_input_general( chop($_POST['ar_subject_value']) );
  }

  if (isset($_POST['ar_message'])) {
    $ar['message'] = filter_input_general( $_POST['ar_message'] );
  }

  if (isset($_POST['ar_quote'])) {
    if ($_POST['ar_quote'] == 'inline') {
      $ar['quote'] = 'inline';
    }
    if ($_POST['ar_quote'] == 'attach') {
      $ar['quote'] = 'attach';
    }
  }

  if (isset($_POST['ar_from']) && $_POST['ar_from'] == 'custom' && isset($_POST['ar_fromname'])) {
    $ar['fromname'] = filter_input_general( $_POST['ar_fromname']);
  }
    
  $account['autoresponder'] = $ar;



  if (isset($_POST['forward']) && $_POST['forward'] == 'yes')
  {
    $num = 1;
    while (true)
    {
      if (! isset($_POST['forward_to_'.$num]))
        break;
      if ($_POST['forward_to_'.$num] == '')
        break;
      $fwd = array("spamfilter" => $_POST['spamfilter_action_'.$num], "destination" => chop($_POST['forward_to_'.$num]));
      array_push($account['forwards'], $fwd);
      $num++;
    }
    if ($num == 1) system_failure("Bitte mindestens eine Weiterleitungsadresse angeben.");
  }

  if ((isset($_POST['forward']) && $_POST['forward']!='yes') && ($_POST['mailbox']!='yes'))
    system_failure("Entweder eine Mailbox oder eine Weiterleitung muss angegeben werden!");

  DEBUG($account);

  save_vmail_account($account);

  if (! ($debugmode || we_have_an_error()))
    header('Location: vmail');
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
    are_you_sure("action=delete&id={$account['id']}", "Möchten Sie die E-Mail-Adresse »{$account_string}« wirklich löschen?");
  }
  elseif ($sure === true)
  {
    delete_account($account['id']);
    if (! $debugmode)
      header("Location: vmail");
  }
  elseif ($sure === false)
  {
    if (! $debugmode)
      header("Location: vmail");
  }

}
else
  system_failure("Unimplemented action");

output('');


?>

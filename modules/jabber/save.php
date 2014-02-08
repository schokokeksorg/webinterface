<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2014 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('session/start.php');

require_once('jabberaccounts.php');

require_once('inc/security.php');


require_role(ROLE_CUSTOMER);

require_once("inc/debug.php");
global $debugmode;

if ($_GET['action'] == 'new')
{
  check_form_token('jabber_new_account');
  if (filter_input_username($_POST['local']) == '' ||
      $_POST['domain'] == '' ||
      $_POST['password'] == '')
  {
    input_error('Sie müssen alle Felder ausfüllen!');
  }
  else
  {
    create_jabber_account($_POST['local'], $_POST['domain'], stripslashes($_POST['password']));
    if (! $debugmode)
      header('Location: accounts');
  }
}
elseif ($_GET['action'] == 'chpass')
{
  check_form_token('jabber_chpass');
  get_jabberaccount_details($_POST['accountid']);
  if ($_POST['newpass'] == '' ||
      $_POST['newpass2'] == '' ||
      $_POST['newpass'] != $_POST['newpass2'] ||
      $_POST['accountid'] == '')
  {
    input_error('Bitte zweimal ein neues Passwort eingeben!');
  }
  else
  {
    change_jabber_password($_POST['accountid'], stripslashes($_POST['newpass']));
    if (! $debugmode)
      header('Location: accounts');
  }
}
elseif ($_GET['action'] == 'delete')
{
  $title = "Jabber-Account löschen";
  $section = 'jabber_accounts';
  
  $account = get_jabberaccount_details($_GET['account']);
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

}
elseif ($_GET['action'] == 'newdomain')
{
  check_form_token('jabber_new_domain');
  new_jabber_domain( $_REQUEST['domain'] );
  header("Location: accounts");
}
else
  system_failure("Unimplemented action");

output('');


?>

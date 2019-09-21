<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2018 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('session/start.php');

require_once('useraccounts.php');

require_once('inc/security.php');


require_role(array(ROLE_CUSTOMER, ROLE_SYSTEMUSER));

$role = $_SESSION['role'];

require_once("inc/debug.php");
global $debugmode;

if ($_GET['action'] == 'new') {
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
    header('Location: account');
}
*/
} elseif ($_GET['action'] == 'pwchange') {
    if (! $role & ROLE_CUSTOMER) {
        system_failure("Zum Ändern Ihres Passworts verwenden Sie bitte die Funktion im Hauptmenü!");
    }
    $error = false;
    check_form_token('systemuser_pwchange');
    if (customer_useraccount($_REQUEST['uid'])) {
        system_failure('Zum Ändern dieses Passworts verwenden Sie bitte die Funktion im Hauptmenü!');
    }

    //if (! strong_password($_POST['newpass']))
    //  input_error('Das Passwort ist zu einfach');
    //else
    if ($_POST['newpass1'] == '' ||
      $_POST['newpass1'] != $_POST['newpass2']) {
        input_error('Bitte zweimal ein neues Passwort eingeben!');
        $error = true;
    } else {
        $user = get_account_details($_REQUEST['uid']);
        # set_systemuser_password kommt aus den Session-Funktionen!
        set_systemuser_password($user['uid'], $_POST['newpass1']);
    }
    if (! ($debugmode || $error)) {
        header('Location: account');
    }
} elseif ($_GET['action'] == 'edit') {
    check_form_token('systemuser_edit');
    $account = null;
    if ($role & ROLE_CUSTOMER) {
        $account = get_account_details($_REQUEST['uid']);
    } else {
        $account = get_account_details($_SESSION['userinfo']['uid'], $_SESSION['userinfo']['customerno']);
    }

    if ($role & ROLE_CUSTOMER) {
        $customerquota = get_customer_quota();
        $maxquota = $customerquota['max'] - $customerquota['assigned'] + $account['quota'];

        $quota = (int) $_POST['quota'];
        if ($quota > $maxquota) {
            system_failure("Sie können diesem Account maximal {$maxquota} MB Speicherplatz zuweisen.");
        }
        $account['quota'] = $quota;
    }

    if ($_POST['defaultname'] == 1) {
        $account['name'] = null;
    } else {
        $account['name'] = filter_input_oneline($_POST['fullname']);
    }

    $shells = available_shells();
    if (isset($shells[$_POST['shell']])) {
        $account['shell'] = $_POST['shell'];
    } elseif (isset($_POST['shell']) && $_POST['shell'] != '') {
        system_failure('Ungültige Shell');
    }

    set_account_details($account);
    if (! ($debugmode)) {
        $location = 'myaccount';
        if ($_SESSION['role'] & ROLE_CUSTOMER) {
            $location = 'account';
        }
        header('Location: '.$location);
    }
} elseif ($_GET['action'] == 'delete') {
    system_failure("Benutzeraccounts zu löschen ist momentan nicht über diese Oberfläche möglich. Bitte wenden Sie sich an einen Administrator.");
/*
$account_string = filter_output_html($account['local'].'@'.$account['domain']);
$sure = user_is_sure();
if ($sure === NULL)
{
  are_you_sure("action=delete&account={$_GET['account']}", "Möchten Sie den Account »{$account_string}« wirklich löschen?");
}
elseif ($sure === true)
{
  delete_jabber_account($account['id']);
  if (! $debugmode)
    header("Location: account");
}
elseif ($sure === false)
{
  if (! $debugmode)
    header("Location: account");
}
*/
} else {
    system_failure("Unimplemented action");
}

output('');

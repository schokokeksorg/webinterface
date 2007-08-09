<?php

require_once('session/start.php');

require_once('class/domain.php');
require_once('mailaccounts.php');

require_role(ROLE_SYSTEMUSER);

$user = $_SESSION['userinfo'];

$title = "E-Mail-Accounts";


if (isset($_GET['action']) && $_GET['action'] == 'save')
{
  if (isset($_GET['id']))
  {
    check_form_token('imap_accounts_edit');
    $account = $_POST['user'].'@'.$_POST['domain'];
    if (isset($_POST['enabled']) && $_POST['enabled'] == 'true')
      $enabled = 'Y';
    else
      $enabled = 'N';
    $acc = array('id' => $_GET['id'], 'account' => $account, 'mailbox' => $_POST['mailbox'], 'enabled' => $enabled);
    if ($_POST['password'] != '')
      $acc['password'] = $_POST['password'];
    $error = check_valid($acc);
    if ($error != "")
    {
      input_error($error);
      $title = "E-Mail-Accounts";
      output("");
    }
    else
    {
      change_mailaccount($_GET['id'], $acc);
      if (! $debugmode)
        header('Location: accounts.php');
      die();
    }
  }
  elseif (isset($_POST['create']))
  {
    check_form_token('imap_accounts_create');
    $account = $_POST['user'].'@'.$_POST['domain'];
    if (isset($_POST['enabled']) && $_POST['enabled'] == 'true')
      $enabled = 'Y';
    else
      $enabled = 'N';
    $acc = array('account' => $account, 'mailbox' => $_POST['mailbox'], 'enabled' => $enabled);
    if ($_POST['password'] != '')
      $acc['password'] = $_POST['password'];
    $error = check_valid($acc);
    if ($error != "")
    {
      input_error($error);
      output("");
    }
    else
    {
      create_mailaccount($acc);
      if (! $debugmode)
        header('Location: accounts.php');
      die(); 
    }
  }
}
elseif (isset($_GET['action']) && $_GET['action'] == 'create')
{
  $options = '';
  $domains = get_domain_list($user['customerno'], $user['uid']);
  if (count($domains) > 0)
    $options .= '<option>----------------------------</option>';
  foreach ($domains as $dom)
    $options .= '<option value="'.$dom->fqdn.'">'.$dom->fqdn.'</option>';

  output('<h3>E-Mail-Account anlegen</h3>
<p>Hier k&ouml;nnen Sie ein neues POP3/IMAP-Konto anlegen.</p>
  '.html_form('imap_accounts_create', 'accounts.php', 'action=save', '
  <table style="margin-bottom: 1em;">
  <tr><th>Einstellung:</th><th>Wert:</th><th>&nbsp;</th></tr>
  <tr>
    <td>Benutzername:</td>
    <td><input type="text" id="user" name="user" />@<select name="domain" size="1">
    <option value="schokokeks.org">schokokeks.org</option>
  '.$options.'
    </select></td>
  </tr>
  <tr>
    <td>Mailbox:</td>
    <td><input type="text" id="mailbox" name="mailbox" value="'.$user['homedir'].'/" /></td>
  </tr>
  <tr>
    <td>Passwort:</td>
    <td><input type="password" id="password" name="password" value="" /></td>
  </tr>
  <tr>
    <td>Account sofort aktivieren:</td>
    <td><input type="checkbox" id="enabled" name="enabled" value="true" /></td>
  </tr>
  </table>
  <p><input type="submit" name="create" value="Anlegen" /></p>
  '));
}
elseif (isset($_GET['action']) && $_GET['action'] == 'delete' && $_GET['account'] != '')
{
  $sure = user_is_sure();
  if ($sure === NULL)
  {
    $_GET['account'] = (int) $_GET['account'];
    $account = get_mailaccount($_GET['account']);
    $enabled = ($account['enabled'] ? 'Ja' : 'Nein');
    are_you_sure("action=delete&amp;account={$_GET['account']}", '
    <p>Soll der folgende Account wirklich gel&ouml;scht werden?</p>
    <table style="margin-bottom: 1em;">
      <tr><td>Benutzername:</td>
        <td>'.filter_input_general($account['account']).'</td>
      </tr>
      <tr><td>Mailbox:</td>
        <td>'.filter_input_general($account['mailbox']).'</td>
      </tr>
      <tr><td>Konto aktiv:</td>
        <td>'.$enabled.'</td>
      </tr>
    </table>
');
  }
  elseif ($sure === true)
  {
    delete_mailaccount($_GET['account']);
    if (! $debugmode)
      header('Location: accounts.php');
    die();
  }
  elseif ($sure === false)
  {
    if (! $debugmode)
      header("Location: accounts.php");
    die();
  }
}
elseif (isset($_GET['edit']))
{
  output('<h3>E-Mail-Account bearbeiten</h3>
<p>Hier k&ouml;nnen Sie die Einstellungen des IMAP-Kontos bearbeiten.</p>
');
  $_GET['edit'] = (int) $_GET['edit'];
  $account = get_mailaccount($_GET['edit']);
  list($username, $domain) = explode('@', $account['account'], 2);
  $enabled = ($account['enabled'] ? ' checked="checked"' : '');
  $form = '
  <table style="margin-bottom: 1em;">
  <tr><th>Einstellung:</th><th>alter Wert:</th><th>neuer Wert:</th><th>&nbsp;</th></tr>
  <tr><td>Benutzername:</td><td><input type="text" id="old_account" name="old_account" value="'.$account['account'].'" readonly="readonly" style="background-color: #C0C0C0;" /></td>
          <td><input type="text" id="user" name="user" value="'.$username.'" />@<select name="domain" id="domain" size="1">
    <option value="schokokeks.org">schokokeks.org</option>
    ';
    $domains = get_domain_list($user['customerno'], $user['uid']);
    if (count($domains) > 0)
      $form .= '<option>----------------------------</option>';
    foreach ($domains as $dom)
      if ($domain == $dom->fqdn)
        $form .= '<option value="'.$dom->fqdn.'" selected="selected">'.$dom->fqdn.'</option>';
      else
        $form .= '<option value="'.$dom->fqdn.'">'.$dom->fqdn.'</option>';

    $form .= '</select></td>
          <td><input type="button" onclick="document.getElementById(\'user\').value = \''.$username.'\' ; document.getElementById(\'domain\').value = \''.$domain.'\'" value="Zeile zur&uuml;cksetzen" /></td></tr>
  <tr><td>Mailbox:</td><td><input type="text" id="old_mailbox" name="old_mailbox" value="'.$account['mailbox'].'" readonly="readonly" style="background-color: #C0C0C0;" /></td>
          <td><input type="text" id="mailbox" name="mailbox" value="'.$account['mailbox'].'" /></td>
          <td><input type="button" onclick="document.getElementById(\'mailbox\').value = document.getElementById(\'old_mailbox\').value" value="Zeile zur&uuml;cksetzen" /></td></tr>
  <tr><td>Passwort:</td><td><i>nicht angezeigt</i></td>
          <td><input type="password" id="password" name="password" value="" /></td>
          <td><input type="button" onclick="document.getElementById(\'password\').value = \'\'" value="Zeile zur&uuml;cksetzen" /></td></tr>
  <tr><td>Konto aktiv:</td>
    <td>&nbsp;</td>
    <td><input type="checkbox" id="enabled" name="enabled" value="true"'.$enabled.' /></td>
    <td>&nbsp;</td></tr>
  </table>
  <p><input type="submit" value="&Auml;nderungen speichern" /><br />
  Hinweis: Das Passwort wird nur ge&auml;ndert, wenn Sie auf dieser Seite eines eingeben. Geben Sie keines an, wird das bisherige beibehalten!</p>
  ';
  output(html_form('imap_accounts_edit', 'accounts.php', 'action=save&id='.$_GET['edit'], $form));
}
else
{
  output('<h3>E-Mail-Accounts</h3>
<p>Folgende POP3/IMAP-Konten sind eingerichtet:</p>
<table style="margin-bottom: 1em;">
<tr><th>Kontoname:</th><th>Mailbox-Pfad:</th><th>aktiv</th><th>&nbsp;</th></tr>
');

        foreach (mailaccounts($user['uid']) as $account)
        {
                $mailbox = $account['mailbox'];
                if (empty($mailbox))
                        $mailbox = '<i>nicht festgelegt</i>';
                output('<tr>
            <td>'.internal_link('accounts.php', $account['account'], 'edit='.$account['id']).'</td>
            <td>'.$mailbox.'</td>
            <td><b>'.($account['enabled'] ? 'Ja' : 'Nein').'</b></td>
            <td><a href="accounts.php?action=delete&amp;account='.$account['id'].'">l&ouml;schen</a></td></tr>');
        }
        output('</table>
<p><a href="accounts.php?action=create">Neuen Account anlegen</a></p>

');
}

?>

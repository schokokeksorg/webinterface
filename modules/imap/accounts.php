<?php

require_once('session/start.php');

require_once('class/domain.php');
require_once('mailaccounts.php');

require_role(ROLE_SYSTEMUSER);

$user = $_SESSION['userinfo'];

$param = '';
if ($debugmode)
        $param="debug";

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
      $section = "mail";
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
  output('<h3>E-Mail-Account anlegen</h3>
<p>Hier k&ouml;nnen Sie ein neues POP3/IMAP-Konto anlegen.</p>
  <form action="accounts.php?action=save&'.$param.'" method="post">
  '.generate_form_token('imap_accounts_create').'
  <table style="margin-bottom: 1em;">
  <tr><th>Einstellung:</th><th>Wert:</th><th>&nbsp;</th></tr>
  <tr>
    <td>Benutzername:</td>
    <td><input type="text" id="user" name="user" />@<select name="domain" size="1">
    <option value="schokokeks.org">schokokeks.org</option>
    ');
    $domains = get_domain_list($user['customerno'], $user['uid']);
    if (count($domains) > 0)
      output('<option>----------------------------</option>');
    foreach ($domains as $dom)
      output('<option value="'.$dom->fqdn.'">'.$dom->fqdn.'</option>');
    output('</select></td>

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
  <p><input type="submit" name="create" value="Anlegen" /><br />
  </form>
  ');
}
elseif (isset($_GET['action']) && $_GET['action'] == 'delete' && $_GET['account'] != '')
{
  if ($_POST['confirm'] == 'yes')
  {
    check_form_token('imap_accounts_delete');
    delete_mailaccount($_GET['account']);
    if (! $debugmode)
      header('Location: accounts.php');
    die();
  }
  else
  {
    output('<h3>E-Mail-Account l&ouml;schen</h3>
    <p>Soll der folgende Account wirklich gel&ouml;scht werden?</p>
    ');
    $_GET['account'] = (int) $_GET['account'];
    $account = get_mailaccount($_GET['account']);
    $enabled = ($account['enabled'] ? 'Ja' : 'Nein');
    output('<form action="accounts.php?action=delete&amp;account='.$_GET['account'].'&amp;'.$param.'" method="post">
    '.generate_form_token('imap_accounts_delete').'
    <table style="margin-bottom: 1em;">
    <tr><td>Benutzername:</td>
      <td>'.$account['account'].'</td>
    </tr>
    <tr><td>Mailbox:</td>
      <td>'.$account['mailbox'].'</td>
    </tr>
    <tr><td>Konto aktiv:</td>
      <td>'.$enabled.'</td>
  </table>
  <p><input type="hidden" name="confirm" value="yes" />
    <input type="submit" value="Wirklich l&ouml;schen" />
  </p>
  </form>
  ');
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
  output('<form action="accounts.php?action=save&amp;id='.$_GET['edit'].'&amp;'.$param.'" method="post">
  '.generate_form_token('imap_accounts_edit').'
  <table style="margin-bottom: 1em;">
  <tr><th>Einstellung:</th><th>alter Wert:</th><th>neuer Wert:</th><th>&nbsp;</th></tr>
  <tr><td>Benutzername:</td><td><input type="text" id="old_account" name="old_account" value="'.$account['account'].'" readonly="readonly" style="background-color: #C0C0C0;" /></td>
          <td><input type="text" id="user" name="user" value="'.$username.'" />@<select name="domain" id="domain" size="1">
    <option value="schokokeks.org">schokokeks.org</option>
    ');
    $domains = get_domain_list($user['customerno'], $user['uid']);
    if (count($domains) > 0)
      output('<option>----------------------------</option>');
    foreach ($domains as $dom)
      if ($domain == $dom->fqdn)
        output('<option value="'.$dom->fqdn.'" selected="selected">'.$dom->fqdn.'</option>');
      else
        output('<option value="'.$dom->fqdn.'">'.$dom->fqdn.'</option>');

    output('</select></td>
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
  </form>
  ');

}
else
{
  output('<h3>E-Mail-Accounts</h3>
<p>Folgende POP3/IMAP-Konten sind eingerichtet:</p>
<table style="margin-bottom: 1em;">
<tr><th>Benutzername:</th><th>Mailbox-Pfad:</th><th>aktiv</th><th>&nbsp;</th></tr>
');

        foreach (mailaccounts($user['uid']) as $account)
        {
                $mailbox = $account['mailbox'];
                if (empty($mailbox))
                        $mailbox = '<i>nicht festgelegt</i>';
                output('<tr>
            <td>'.$account['account'].'</td>
            <td>'.$mailbox.'</td>
            <td><b>'.($account['enabled'] ? 'Ja' : 'Nein').'</b></td>
            <td><a href="accounts.php?edit='.$account['id'].'">bearbeiten</a></td><td><a href="accounts.php?action=delete&amp;account='.$account['id'].'">l&ouml;schen</a></td></li>');
        }
        output('</table>
<p><a href="accounts.php?action=create">Neuen Account anlegen</a></p>

');
}

?>

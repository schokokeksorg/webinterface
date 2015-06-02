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

require_once('class/domain.php');
require_once('mailaccounts.php');

require_once('inc/icons.php');

require_role(ROLE_SYSTEMUSER);


$user = $_SESSION['userinfo'];

title("E-Mail-Accounts");


if (isset($_GET['action']) && $_GET['action'] == 'save')
{
  if (isset($_GET['id']))
  {
    check_form_token('email_imap_edit');
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
        header('Location: imap');
      die();
    }
  }
  elseif (isset($_POST['create']))
  {
    check_form_token('email_imap_create');
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
      system_failure($error);
    }
    else
    {
      create_mailaccount($acc);
      if (! $debugmode)
        header('Location: imap');
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

  title("IMAP-Account anlegen");
  output('<p>Hier können Sie ein neues POP3/IMAP-Konto anlegen.</p>
<p style="border: 2px solid red; background-color: white; padding:1em;"><strong>ACHTUNG:</strong> ein POP3-/IMAP-Account ist <strong>keine E-Mail-Adresse</strong>. Wenn Sie sich nicht sicher sind, lesen Sie bitte die Anleitung <a href="https://wiki.schokokeks.org/E-Mail/Konfiguration">in unserem Wiki</a>. Sie können Ihre E-Mail-Konten auch über eine einfachere Möglichkeit verwalten, dann ist eine Einrichtung über diese Weboberfläche möglich. Die Umstellung erfolgt '.internal_link("../email/domains", "unter Domains").'.</p>
  '.html_form('email_imap_create', 'imap', 'action=save', '
  <table style="margin-bottom: 1em;">
  <tr><th>Einstellung:</th><th>Wert:</th><th>&#160;</th></tr>
  <tr>
    <td>Benutzername:</td>
    <td><input type="text" id="user" name="user" />@<select name="domain" size="1">
    <option value="'.config('masterdomain').'">'.config('masterdomain').'</option>
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
    are_you_sure("action=delete&account={$_GET['account']}", '
    <p>Soll der folgende Account wirklich gelöscht werden?</p>
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
      header('Location: imap');
    die();
  }
  elseif ($sure === false)
  {
    if (! $debugmode)
      header("Location: imap");
    die();
  }
}
elseif (isset($_GET['edit']))
{
  title("IMAP-Account bearbeiten");
  output('<p>Hier können Sie die Einstellungen des IMAP-Kontos bearbeiten.</p>
');
  $_GET['edit'] = (int) $_GET['edit'];
  $account = get_mailaccount($_GET['edit']);
  list($username, $domain) = explode('@', $account['account'], 2);
  $enabled = ($account['enabled'] ? ' checked="checked"' : '');
  $form = '
  <table style="margin-bottom: 1em;">
  <tr><th>Einstellung:</th><th>alter Wert:</th><th>neuer Wert:</th><th>&#160;</th></tr>
  <tr><td>Benutzername:</td><td><input type="text" id="old_account" name="old_account" value="'.$account['account'].'" readonly="readonly" style="background-color: #C0C0C0;" /></td>
          <td><input type="text" id="user" name="user" value="'.$username.'" />@<select name="domain" id="domain" size="1">
    <option value="'.config('masterdomain').'">'.config('masterdomain').'</option>
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
          <td><input type="button" onclick="document.getElementById(\'user\').value = \''.$username.'\' ; document.getElementById(\'domain\').value = \''.$domain.'\'" value="Zeile zurücksetzen" /></td></tr>
  <tr><td>Mailbox:</td><td><input type="text" id="old_mailbox" name="old_mailbox" value="'.$account['mailbox'].'" readonly="readonly" style="background-color: #C0C0C0;" /></td>
          <td><input type="text" id="mailbox" name="mailbox" value="'.$account['mailbox'].'" /></td>
          <td><input type="button" onclick="document.getElementById(\'mailbox\').value = document.getElementById(\'old_mailbox\').value" value="Zeile zurücksetzen" /></td></tr>
  <tr><td>Passwort:</td><td><i>nicht angezeigt</i></td>
          <td><input type="password" id="password" name="password" value="" /></td>
          <td><input type="button" onclick="document.getElementById(\'password\').value = \'\'" value="Zeile zurücksetzen" /></td></tr>
  <tr><td>Konto aktiv:</td>
    <td>&#160;</td>
    <td><input type="checkbox" id="enabled" name="enabled" value="true"'.$enabled.' /></td>
    <td>&#160;</td></tr>
  </table>
  <p><input type="submit" value="Änderungen speichern" /><br />
  Hinweis: Das Passwort wird nur geändert, wenn Sie auf dieser Seite eines eingeben. Geben Sie keines an, wird das bisherige beibehalten!</p>
  ';
  output(html_form('email_imap_edit', 'imap', 'action=save&id='.$_GET['edit'], $form));
}
else
{
  title("IMAP-Accounts");
  if (user_has_only_vmail_domains())
  {
    output('<div class="error"><strong>Achtung:</strong> Alle Ihre Domains sind auf Webinterface-Verwaltung konfiguriert. Sie können dennoch manuelle IMAP-Konten für Ihre speziellen Konfigurationen anlegen, in der Regel sollten Sie aber hier keine IMAP-Acccounts anlegen. Dies kann zu Fehlfunktionen führen.</div>');
  }
  addnew("imap", "Neuen Account anlegen", "action=create");
  output('<p>Folgende POP3/IMAP-Konten sind eingerichtet:</p>
<table style="margin-bottom: 1em;">
<tr><th>Kontoname:</th><th>Mailbox-Pfad:</th><th>aktiv</th><th>&#160;</th></tr>
');

        foreach (mailaccounts($user['uid']) as $account)
        {
                $mailbox = $account['mailbox'];
                if (empty($mailbox))
                        $mailbox = '<i>nicht festgelegt</i>';
                output('<tr>
            <td>'.internal_link('imap', $account['account'], 'edit='.$account['id']).'</td>
            <td>'.$mailbox.'</td>
            <td><b>'.($account['enabled'] ? 'Ja' : 'Nein').'</b></td>
            <td>'.internal_link("imap", icon_delete("»{$account['account']}« löschen"), "action=delete&account=".$account['id']).'</td></tr>');
        }
  output('</table>');
  if (imap_on_vmail_domain())
  {
    output('<div class="error"><strong>Achtung:</strong> Es scheint als hätten Sie einen (manuellen) IMAP-Account mittels einer Domain angelegt, die für Webinterface-Verwaltung konfiguriert ist. Sollten Sie nicht genau wissen was Sie tun, ist das vermutlich falsch und wird zu Fehlfunktionen führen.</div>');
  }
  addnew("imap", "Neuen Account anlegen", "action=create");

  output('<p>'.other_icon('information.png', 'Zugangsdaten anzeigen').' <strong>'.internal_link('logindata', 'Zugangsdaten für E-Mail-Abruf anzeigen', 'server='.get_server_by_id($_SESSION['userinfo']['server']).'&type=manual').'</strong></p>');
}

?>

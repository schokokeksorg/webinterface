<?php

require_once('inc/debug.php');
require_once('inc/security.php');

require_once('vmail.php');

$title = "E-Mail-Adresse bearbeiten";
$section = 'vmail_accounts';
require_role(ROLE_SYSTEMUSER);

$id = (int) $_GET['id'];
$vhost = empty_account();

if ($id != 0)
  $account = get_account_details($id);

DEBUG($account);
if ($id == 0) {
  output("<h3>Neue E-Mail-Adresse anlegen</h3>");
  $title = "E-Mail-Adresse anlegen";
}
else {
  output("<h3>E-Mail-Adresse bearbeiten</h3>");
}


$is_forward = ($mailbox['type'] == 'forward');
$is_mailbox = ( ! $is_forward);

output("<script type=\"text/javascript\">
  
  function selectForwarding() {
    // document.getElementById('forward_options').style.visibility = 'visible';
    // document.getElementById('mailbox_options').style.visibility = 'hidden';
    document.getElementById('forward_options').style.display = 'block';
    document.getElementById('mailbox_options').style.display = 'none';
    document.getElementById('spamfilter_folder').disabled = true;
    document.getElementById('virusfilter_folder').disabled = true;
    }
  
  function selectMailbox() {
    // document.getElementById('mailbox_options').style.visibility = 'visible';
    // document.getElementById('forward_options').style.visibility = 'hidden';
    document.getElementById('mailbox_options').style.display = 'block';
    document.getElementById('forward_options').style.display = 'none';
    document.getElementById('spamfilter_folder').disabled = false;
    document.getElementById('virusfilter_folder').disabled = false;
    }
  
  function toggleSpamfilterOptions() {
    if (document.getElementById('spamfilter').checked)
      document.getElementById('spamfilter_options').style.display = 'block';
    else
      document.getElementById('spamfilter_options').style.display = 'none';
    }
  
  function toggleVirusfilterOptions() {
    if (document.getElementById('virusfilter').checked)
      document.getElementById('virusfilter_options').style.display = 'block';
    else
      document.getElementById('virusfilter_options').style.display = 'none';
    }

  </script>");

$form = "
    <p><strong>E-Mail-Adresse:</strong>&#160;<input type=\"text\" name=\"local\" id=\"local\" size=\"10\" value=\"{$account['local']}\" /><strong style=\"font-size: 1.5em;\">&#160;@&#160;</strong>".domainselect($account['domainid'])."</p>";

$form .= "<p><input type=\"checkbox\" id=\"spamfilter\" name=\"spamfilter\" value=\"1\" ".($account['spamfilter'] != NULL ? 'checked="checked" ' : '')." onclick=\"toggleSpamfilterOptions()\" /><label for=\"spamfilter\">&#160;Spam-Filter</label></p>";

$form .= "<p style=\"margin-left: 2em; ".($account['spamfilter'] != NULL ? '' : 'display: none;')."\" id=\"spamfilter_options\">
  <em>Was soll mit E-Mails geschehen, die als Spam eingestuft wurden?</em><br />
  <input type=\"radio\" id=\"spamfilter_folder\" name=\"spamfilter_action\" value=\"folder\" ".($account['spamfilter'] == 'folder' ? 'checked="checked" ' : '')."/><label for=\"spamfilter_folder\">&#160;In IMAP-Unterordner »Spam« ablegen</label><br />
  <input type=\"radio\" id=\"spamfilter_tag\" name=\"spamfilter_action\" value=\"tag\" ".($account['spamfilter'] == 'tag' ? 'checked="checked" ' : '')."/><label for=\"spamfilter_tag\">&#160;Markieren und ganz normal zustellen</label><br />
<input type=\"radio\" id=\"spamfilter_delete\" name=\"spamfilter_action\" value=\"delete\" ".($account['spamfilter'] == 'delete' ? 'checked="checked" ' : '')."/><label for=\"spamfilter_delete\">&#160;Löschen</label>
  </p>
  ";

  
$form .= "<p><input type=\"checkbox\" id=\"virusfilter\" name=\"virusfilter\" value=\"1\" ".($account['virusfilter'] != NULL ? 'checked="checked" ' : '')." onclick=\"toggleVirusfilterOptions()\" /><label for=\"virusfilter\">&#160;Viren-Scanner</label></p>";

$form .= "<p style=\"margin-left: 2em; ".($account['virusfilter'] != NULL ? '' : 'display: none;')."\" id=\"virusfilter_options\">
  <em>Was soll mit E-Mails geschehen, in denen ein Virus erkannt wurde?</em><br />
  <input type=\"radio\" id=\"virusfilter_folder\" name=\"virusfilter_action\" value=\"folder\" ".($account['virusfilter'] == 'folder' ? 'checked="checked" ' : '')."/><label for=\"virusfilter_folder\">&#160;In IMAP-Unterordner »Viren« ablegen</label><br />
  <input type=\"radio\" id=\"virusfilter_tag\" name=\"virusfilter_action\" value=\"tag\" ".($account['virusfilter'] == 'tag' ? 'checked="checked" ' : '')."/><label for=\"virusfilter_tag\">&#160;Markieren und ganz normal zustellen</label><br />
<input type=\"radio\" id=\"virusfilter_delete\" name=\"virusfilter_action\" value=\"delete\" ".($account['virusfilter'] == 'delete' ? 'checked="checked" ' : '')."/><label for=\"virusfilter_delete\">&#160;Löschen</label>
  </p>
  ";

$password_message = '';
if ($is_mailbox and ($account['data'] != ''))
  $password_message = '<spam style="font-size: 80%"><br /><em>Sie haben bereits ein Passwort gesetzt. Wenn Sie dieses Feld leer lassen, wird das bisherige Passwort beibehalten.</em></span>';
  

$form .= "<p>
    <input type=\"radio\" id=\"forward\" name=\"type\" value=\"forward\" ".($is_forward ? 'checked="checked" ' : '')." onclick=\"selectForwarding()\" /><label for=\"forward\">&#160;Weiterleitung an andere E-Mail-Adresse</label></p>
    <p style=\"margin-left: 2em; ".($is_mailbox ? 'display: none' : '')."\" id=\"forward_options\">Weiterleitung an:&#160;<input type=\"text\" id=\"forward_to\" name=\"forward_to\" value=\"".($is_forward ? $account['data'] : '')."\" /></p>
    <p><input type=\"radio\" id=\"mailbox\" name=\"type\" value=\"mailbox\" ".($is_mailbox ? 'checked="checked" ' : '')." onclick=\"selectMailbox()\" /><label for=\"mailbox\">&#160;In Mailbox speichern</label></p>
    <p style=\"margin-left: 2em; ".($is_forward ? 'display: none' : '')."\" id=\"mailbox_options\">Passwort für Abruf:&#160;<input type=\"password\" id=\"password\" name=\"password\" value=\"\" />{$password_message}</p>";
    
/*
$form .= "<tr><td>PHP</td>
    <td><select name=\"php\" id=\"php\">
      <option value=\"none\" ".($vhost['php'] == NULL ? 'selected="selected"' : '')." >kein PHP</option>
      <option value=\"mod_php\" ".($vhost['php'] == 'mod_php' ? 'selected="selected"' : '')." >als Apache-Modul</option>
      <option value=\"fastcgi\" ".($vhost['php'] == 'fastcgi' ? 'selected="selected"' : '')." >FastCGI</option>
    </select>
    </td>
    <td id=\"defaultphp\">als Apache-Modul</td></tr>
    <tr><td>SSL-Verschlüsselung</td>
    <td><select name=\"ssl\" id=\"ssl\">
      <option value=\"none\" ".($vhost['ssl'] == NULL ? 'selected="selected"' : '')." >SSL optional anbieten</option>
      <option value=\"http\" ".($vhost['ssl'] == 'http' ? 'selected="selected"' : '')." >kein SSL</option>
      <option value=\"https\" ".($vhost['ssl'] == 'https' ? 'selected="selected"' : '')." >nur SSL</option>
      <option value=\"forward\" ".($vhost['ssl'] == 'forward' ? 'selected="selected"' : '')." >Immer auf SSL umleiten</option>
    </select>
    </td>
    <td id=\"defaultssl\">SSL optional anbieten</td></tr>
    <tr>
      <td>Logfiles <span class=\"warning\">*</span></td>
      <td><select name=\"logtype\" id=\"logtype\">
      <option value=\"none\" ".($vhost['logtype'] == NULL ? 'selected="selected"' : '')." >keine Logfiles</option>
      <option value=\"anonymous\" ".($vhost['logtype'] == 'anonymous' ? 'selected="selected"' : '')." >anonymisiert</option>
      <option value=\"default\" ".($vhost['logtype'] == 'default' ? 'selected="selected"' : '')." >vollständige Logfile</option>
    </select><br />
    <input type=\"checkbox\" id=\"errorlog\" name=\"errorlog\" value=\"1\" ".($vhost['errorlog'] == 1 ? ' checked="checked" ' : '')." />&#160;<label for=\"errorlog\">Fehlerprotokoll (error_log) einschalten</label>
    </td>
    <td id=\"defaultlogtype\">keine Logfiles</td></tr>
    ";

*/
$form .= '
  <p><input type="submit" value="Speichern" />&#160;&#160;&#160;&#160;'.internal_link('accounts.php', 'Abbrechen').'</p>';

output(html_form('vmail_edit_mailbox', 'save.php', 'action=edit'.($id != 0 ? '&id='.$id : ''), $form));


?>

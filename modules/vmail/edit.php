<?php

require_once('inc/debug.php');
require_once('inc/security.php');

require_once('vmail.php');

$title = "E-Mail-Adresse bearbeiten";
$section = 'vmail_accounts';
require_role(ROLE_SYSTEMUSER);

$id = (int) $_GET['id'];
$account = empty_account();

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


$is_forward = ($account['type'] == 'forward');
$is_mailbox = ( ! $is_forward);

output("<script type=\"text/javascript\">
  
  var numForwards = 1;

  function moreForward()
  {
    numForwards += 1;

    TR = document.createElement('tr');
    TD1 = document.createElement('td');
    TD2 = document.createElement('td');

    INPUT = document.createElement('input');
    INPUT.type = 'text';
    INPUT.name = 'forward_to_' + numForwards;
    INPUT.value = '';

    SELECT = document.createElement('select');
    SELECT.name = 'spamfilter_action_' + numForwards;

    SELECT.options[0] = new Option('kein Filter', 'none', 1);
    SELECT.options[1] = new Option('markieren und zustellen', 'tag', 0);
    SELECT.options[2] = new Option('nicht zustellen', 'delete', 0);

    TD1.appendChild(INPUT);
    TD2.appendChild(SELECT);

    TR.appendChild(TD1);
    TR.appendChild(TD2);

    table = document.getElementById('forward_table');
    table.appendChild(TR);
  }
</script>
");
/*
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
*/

$form = "
    <p><strong>E-Mail-Adresse:</strong>&#160;<input type=\"text\" name=\"local\" id=\"local\" size=\"10\" value=\"{$account['local']}\" /><strong style=\"font-size: 1.5em;\">&#160;@&#160;</strong>".domainselect($account['domainid'])."</p>";

/*  
$form .= "<p><input type=\"checkbox\" id=\"virusfilter\" name=\"virusfilter\" value=\"1\" ".($account['virusfilter'] != NULL ? 'checked="checked" ' : '')." /><label for=\"virusfilter\">&#160;Viren-Scanner</label></p>";

$form .= "<p style=\"margin-left: 2em;\" id=\"virusfilter_options\">
  <em>Was soll mit E-Mails geschehen, in denen ein Virus erkannt wurde?</em><br />
  <input type=\"radio\" id=\"virusfilter_folder\" name=\"virusfilter_action\" value=\"folder\" ".($account['virusfilter'] == 'folder' ? 'checked="checked" ' : '')."/><label for=\"virusfilter_folder\">&#160;In IMAP-Unterordner »Spam« ablegen</label><br />
  <input type=\"radio\" id=\"virusfilter_tag\" name=\"virusfilter_action\" value=\"tag\" ".($account['virusfilter'] == 'tag' ? 'checked="checked" ' : '')."/><label for=\"virusfilter_tag\">&#160;Markieren und ganz normal zustellen</label><br />
<input type=\"radio\" id=\"virusfilter_delete\" name=\"virusfilter_action\" value=\"delete\" ".($account['virusfilter'] == 'delete' ? 'checked="checked" ' : '')."/><label for=\"virusfilter_delete\">&#160;Löschen</label>
  </p>
  ";
*/

$password_message = '';
if ($is_mailbox and ($account['data'] != ''))
  $password_message = '<span style="font-size: 80%"><br /><em>Sie haben bereits ein Passwort gesetzt. Wenn Sie dieses Feld leer lassen, wird das bisherige Passwort beibehalten.</em></span>';
  

$form .= "
    <p><input type=\"checkbox\" id=\"mailbox\" name=\"type\" value=\"mailbox\" ".($is_mailbox ? 'checked="checked" ' : '')." /><label for=\"mailbox\">&#160;In Mailbox speichern</label></p>
    <p style=\"margin-left: 2em;\" id=\"mailbox_options\">Passwort für Abruf:&#160;<input type=\"password\" id=\"password\" name=\"password\" value=\"\" />{$password_message}</p>";


$form .= "
<p style=\"margin-left: 2em;\" class=\"spamfilter_options\">
  <em>Wählen Sie, was mit unerwünschten E-Mails (Spam, Viren) passieren soll</em><br />
  <input type=\"radio\" id=\"spamfilter_none\" name=\"spamfilter_action\" value=\"none\" ".($account['spamfilter'] == NULL ? 'checked="checked" ' : '')."/><label for=\"spamfilter_none\">&#160;Keine Überprüfung durchführen (alle E-Mails zustellen)</label><br />
  <input type=\"radio\" id=\"spamfilter_folder\" name=\"spamfilter_action\" value=\"folder\" ".($account['spamfilter'] == 'folder' ? 'checked="checked" ' : '')."/><label for=\"spamfilter_folder\">&#160;In IMAP-Unterordner »Spam« ablegen</label><br />
  <input type=\"radio\" id=\"spamfilter_tag\" name=\"spamfilter_action\" value=\"tag\" ".($account['spamfilter'] == 'tag' ? 'checked="checked" ' : '')."/><label for=\"spamfilter_tag\">&#160;Markieren und ganz normal zustellen</label><br />
  <input type=\"radio\" id=\"spamfilter_delete\" name=\"spamfilter_action\" value=\"delete\" ".($account['spamfilter'] == 'delete' ? 'checked="checked" ' : '')."/><label for=\"spamfilter_delete\">&#160;Nicht zustellen (Löschen)</label>
</p>
  ";

$form .= "<p><input type=\"checkbox\" id=\"forward\" name=\"type\" value=\"forward\" ".($is_forward ? 'checked="checked" ' : '')." /><label for=\"forward\">&#160;Weiterleitung an andere E-Mail-Adressen</label></p>";

$form .= "<table style=\"margin-left: 2em;\" id=\"forward_table\">
<tr><th>Ziel-Adresse</th><th>Unerwünschte E-Mails</th></tr>
";

$form .= "
<tr>
  <td><input type=\"text\" id=\"forward_to_1\" name=\"forward_to_1\" value=\"\" /></td>
  <td>
  <select id=\"spamfilter_action_1\" name=\"spamfilter_action_1\">
    <option value=\"none\">kein Filter</option>
    <option value=\"tag\">markieren und zustellen</option>
    <option value=\"delete\">löschen</option>
  </select>
  </td>
</tr>
  ";
    
$form .= '</table>
  <p style="margin-left: 2em;">[ <a href="#" onclick="moreForward();">mehr Empfänger</a> ]</p>
  <p><input type="submit" value="Speichern" />&#160;&#160;&#160;&#160;'.internal_link('accounts.php', 'Abbrechen').'</p>';

output(html_form('vmail_edit_mailbox', 'save.php', 'action=edit'.($id != 0 ? '&id='.$id : ''), $form));


?>

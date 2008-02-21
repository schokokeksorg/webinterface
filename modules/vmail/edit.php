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


$is_forward = (count($account['forwards']) > 0);
$is_mailbox = ($account['password'] != NULL  ||  $id == 0);
$numforwards = max(count($account['forwards']), 1);

output("<script type=\"text/javascript\">
  
  var numForwards = {$numforwards};

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

    SELECT.options[0] = new Option('kein Filter', 'none', 0);
    SELECT.options[1] = new Option('markieren und zustellen', 'tag', 1);
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

$form = "
    <p><strong>E-Mail-Adresse:</strong>&#160;<input type=\"text\" name=\"local\" id=\"local\" size=\"10\" value=\"{$account['local']}\" /><strong style=\"font-size: 1.5em;\">&#160;@&#160;</strong>".domainselect($account['domain'])."</p>";

$password_message = '';
$password_value = '';
if ($is_mailbox and ($account['password'] != ''))
{
  $password_message = '<span style="font-size: 80%"><br /><em>Sie haben bereits ein Passwort gesetzt. Wenn Sie dieses Feld leer lassen, wird das bisherige Passwort beibehalten.</em></span>';
  $password_value = '**********';
} 

$form .= "
    <p><input type=\"checkbox\" id=\"mailbox\" name=\"mailbox\" value=\"yes\" ".($is_mailbox ? 'checked="checked" ' : '')." /><label for=\"mailbox\">&#160;In Mailbox speichern</label></p>
    <p style=\"margin-left: 2em;\" id=\"mailbox_options\">Passwort für Abruf:&#160;<input type=\"password\" id=\"password\" name=\"password\" value=\"{$password_value}\" />{$password_message}</p>";


$form .= "
<p style=\"margin-left: 2em;\" class=\"spamfilter_options\">
  <em>Wählen Sie, was mit unerwünschten E-Mails (Spam, Viren) passieren soll</em><br />";

$form.= "".html_select('spamfilter_action', array("none" => 'kein Filter', "folder" => 'In Unterordner »Spam« ablegen', "tag" => 'markieren und zustellen', "delete" => 'löschen'), $account['spamfilter'])."</p>";

/*  <input type=\"radio\" id=\"spamfilter_none\" name=\"spamfilter_action\" value=\"none\" ".($account['spamfilter'] == NULL ? 'checked="checked" ' : '')."/><label for=\"spamfilter_none\">&#160;Keine Überprüfung durchführen (alle E-Mails zustellen)</label><br />
  <input type=\"radio\" id=\"spamfilter_folder\" name=\"spamfilter_action\" value=\"folder\" ".($account['spamfilter'] == 'folder' ? 'checked="checked" ' : '')."/><label for=\"spamfilter_folder\">&#160;In IMAP-Unterordner »Spam« ablegen</label><br />
  <input type=\"radio\" id=\"spamfilter_tag\" name=\"spamfilter_action\" value=\"tag\" ".($account['spamfilter'] == 'tag' ? 'checked="checked" ' : '')."/><label for=\"spamfilter_tag\">&#160;Markieren und ganz normal zustellen</label><br />
  <input type=\"radio\" id=\"spamfilter_delete\" name=\"spamfilter_action\" value=\"delete\" ".($account['spamfilter'] == 'delete' ? 'checked="checked" ' : '')."/><label for=\"spamfilter_delete\">&#160;Nicht zustellen (Löschen)</label>
</p>
  ";*/

$form .= "<p><input type=\"checkbox\" id=\"forward\" name=\"forward\" value=\"yes\" ".($is_forward ? 'checked="checked" ' : '')." /><label for=\"forward\">&#160;Weiterleitung an andere E-Mail-Adressen</label></p>";

$form .= "<table style=\"margin-left: 2em;\" id=\"forward_table\">
<tr><th>Ziel-Adresse</th><th>Unerwünschte E-Mails</th></tr>
";

if ($is_forward)
{
  for ($i = 0 ; $i < $numforwards ; $i++)
  {
  $num = $i+1;
  $form .= "
<tr>
  <td><input type=\"text\" id=\"forward_to_{$num}\" name=\"forward_to_{$num}\" value=\"{$account['forwards'][$i]['destination']}\" /></td>
  <td>
  ".html_select('spamfilter_action_'.$num, array("none" => 'kein Filter', "tag" => 'markieren und zustellen', "delete" => 'löschen'), $account['forwards'][$i]['spamfilter'])."
  </td>
</tr>
";
  }
}
else
{
  $form .= "
<tr>
  <td><input type=\"text\" id=\"forward_to_1\" name=\"forward_to_1\" value=\"\" /></td>
  <td>
  ".html_select('spamfilter_action_1', array("none" => 'kein Filter', "tag" => 'markieren und zustellen', "delete" => 'löschen'), "tag")."
  </td>
</tr>
  ";
}

$form .= '</table>
  <p style="margin-left: 2em;">[ <a href="#" onclick="moreForward();">mehr Empfänger</a> ]</p>
  <p><input type="submit" value="Speichern" />&#160;&#160;&#160;&#160;'.internal_link('accounts.php', 'Abbrechen').'</p>';

output(html_form('vmail_edit_mailbox', 'save.php', 'action=edit'.($id != 0 ? '&id='.$id : ''), $form));


?>

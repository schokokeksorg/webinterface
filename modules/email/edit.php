<?php

require_once('inc/debug.php');
require_once('inc/security.php');

require_once('vmail.php');

$title = "E-Mail-Adresse bearbeiten";
$section = 'email_vmail';
require_role(ROLE_SYSTEMUSER);

$account = empty_account();
$id = (isset($_GET['id']) ? (int) $_GET['id'] : 0);

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

    P1 = document.createElement('p');

    TXT1 = document.createTextNode('Weiterleiten an ');

    INPUT = document.createElement('input');
    INPUT.type = 'text';
    INPUT.name = 'forward_to_' + numForwards;
    INPUT.value = '';

    P1.appendChild(TXT1);
    P1.appendChild(INPUT);

    P2 = document.createElement('p');

    TXT2 = document.createTextNode('Spam-Mails an diese Adresse ');

    SELECT = document.createElement('select');
    SELECT.name = 'spamfilter_action_' + numForwards;

    SELECT.options[0] = new Option('nicht filtern', 'none', 1);
    SELECT.options[1] = new Option('markieren und zustellen', 'tag', 0);
    SELECT.options[2] = new Option('nicht zustellen', 'delete', 0);

    P2.appendChild(TXT2);
    P2.appendChild(SELECT);

    DIV = document.createElement('div');
    DIV.className = 'vmail-forward';

    DIV.appendChild(P1);
    DIV.appendChild(P2);

    parent = document.getElementById('forward_config');
    parent.appendChild(DIV);
  }
</script>
");

$form = "
    <p><strong>E-Mail-Adresse:</strong>&#160;<input type=\"text\" name=\"local\" id=\"local\" size=\"10\" value=\"{$account['local']}\" /><strong style=\"font-size: 1.5em;\">&#160;@&#160;</strong>".domainselect($account['domain'])."</p>";

$password_message = '';
$password_value = '';
if ($is_mailbox and ($account['password'] != ''))
{
  $password_message = '<span style="font-size: 80%"><br /><em>Sie haben bereits ein Passwort gesetzt. Wenn Sie dieses Feld nicht ändern, wird das bisherige Passwort beibehalten.</em></span>';
  $password_value = '**********';
} 

$form .= "
    <p><input type=\"checkbox\" id=\"mailbox\" name=\"mailbox\" value=\"yes\" ".($is_mailbox ? 'checked="checked" ' : '')." /><label for=\"mailbox\">&#160;<strong>In Mailbox speichern</strong></label></p>
    <p style=\"margin-left: 2em;\" id=\"mailbox_options\">Passwort für Abruf:&#160;<input type=\"password\" id=\"password\" name=\"password\" value=\"{$password_value}\" />{$password_message}</p>";

$form.= "<p style=\"margin-left: 2em;\" class=\"spamfilter_options\">Unerwünschte E-Mails (Spam, Viren) in diesem Postfach ".html_select('spamfilter_action', array("none" => 'nicht filtern', "folder" => 'in Unterordner »Spam« ablegen', "tag" => 'markieren und zustellen', "delete" => 'nicht zustellen (löschen)'), $account['spamfilter'])."</p>";

$form .= "<p><input type=\"checkbox\" id=\"forward\" name=\"forward\" value=\"yes\" ".($is_forward ? 'checked="checked" ' : '')." /><label for=\"forward\">&#160;<strong>Weiterleitung an andere E-Mail-Adressen</strong></label></p>";


$form .= "<div style=\"margin-left: 2em;\" id=\"forward_config\">";

if ($is_forward)
{
  for ($i = 0 ; $i < $numforwards ; $i++)
  {
    $num = $i+1;
    $form .= "<div class=\"vmail-forward\">
    <p>Weiterleiten an <input type=\"text\" id=\"forward_to_{$num}\" name=\"forward_to_{$num}\" value=\"{$account['forwards'][$i]['destination']}\" /></p>
    <p>Spam-Mails an diese Adresse ".html_select('spamfilter_action_'.$num, array("none" => 'nicht filtern', "tag" => 'markieren und zustellen', "delete" => 'nicht zustellen'), $account['forwards'][$i]['spamfilter'])."</p>
    </div>\n";
  }
}
else
{
    $form .= "<div class=\"vmail-forward\">
    <p>Weiterleiten an <input type=\"text\" id=\"forward_to_1\" name=\"forward_to_1\" value=\"\" /></p>
    <p>Spam-Mails an diese Adresse ".html_select('spamfilter_action_1', array("none" => 'nicht filtern', "tag" => 'markieren und zustellen', "delete" => 'nicht zustellen'), "none")."</p>
    </div>\n";
}

$form .= '</div>';

$form .= '<p style="margin-left: 2em;">[ <a href="#" onclick="moreForward();">mehr Empfänger</a> ]</p>
  <p><input type="submit" value="Speichern" />&#160;&#160;&#160;&#160;'.internal_link('vmail', 'Abbrechen').'</p>';

output(html_form('vmail_edit_mailbox', 'save', 'action=edit'.($id != 0 ? '&id='.$id : ''), $form));


?>

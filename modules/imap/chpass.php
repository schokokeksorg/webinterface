<?php

require_once('session/start.php');

require_once('mailaccounts.php');

require_role(ROLE_MAILACCOUNT);

$account = $_SESSION['accountname'];

title("Passwort ändern");

if ($_POST['password1'] != '')
{
  check_form_token('imap_chpass');
  $result = find_role($_SESSION['mailaccount'], $_POST['old_password']);

  if ($_POST['old_password'] == '')
    input_error('Altes Passwort nicht angegeben!');
  elseif (! $result & ROLE_MAILACCOUNT)
    input_error('Das bisherige Passwort ist nicht korrekt!');
  elseif ($_POST['password2'] != $_POST['password1'])
    input_error('Die Bestätigung ist nicht identisch mit dem neuen Passwort!');
  elseif ($_POST['password2'] == '')
    input_error('Sie müssen das neue Passwort zweimal eingeben!');
  elseif (($check = strong_password($_POST['password1'])) !== true)
    input_error("Das Passwort ist zu einfach (cracklib sagt: {$check})!");
  else {
    change_mailaccount(get_mailaccount_id($_SESSION['mailaccount']), array('password' => $_POST['password1']));
    if (! $debugmode)
      header('Location: chpass');
    else
      output('');
  }
}



output('<p>Hier können Sie Ihr Passwort ändern.</p>
'.html_form('imap_chpass', 'chpass', '', '<table>
  <tr>
    <td>bisheriges Passwort:</td>  <td><input type="password" name="old_password" value="" /></td>
  </tr>
  <tr>
    <td>neues Passwort:</td>       <td><input type="password" name="password1" value="" /></td>
  </tr>
  <tr>
    <td>Bestätigung:<br /><span style="font-size: 80%;">(nochmal neues Passwort)</span></td>
                                   <td><input type="password" name="password2" value="" /></td>
  </tr>
</table>
<p><input type="submit" value="Speichern" /></p>
'));




?>

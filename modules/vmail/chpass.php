<?php

require_once('session/start.php');

require_once('vmail.php');

require_role(ROLE_VMAIL_ACCOUNT);

$accname = $_SESSION['mailaccount'];

$title = "Passwort ändern";

if ($_POST['password1'] != '')
{
  check_form_token('vmail_chpass');
  $result = find_role($_SESSION['mailaccount'], $_POST['old_password']);

  if ($_POST['old_password'] == '')
    input_error('Altes Passwort nicht angegeben!');
  elseif (! $result & ROLE_VMAIL_ACCOUNT)
    input_error('Das bisherige Passwort ist nicht korrekt!');
  elseif ($_POST['password2'] != $_POST['password1'])
    input_error('Die Bestätigung ist nicht identisch mit dem neuen Passwort!');
  elseif ($_POST['password2'] == '')
    input_error('Sie müssen das neue Passwort zweimal eingeben!');
  elseif (($check = strong_password($_POST['password1'])) !== true)
    input_error("Das Passwort ist zu einfach (cracklib sagt: {$check})!");
  else {
    change_vmail_password($accname, $_POST['password1']);
    if (! $debugmode)
      header('Location: chpass.php');
    else
      output('');
  }
}



output('<h3>Passwort ändern</h3>
<p>Hier können Sie Ihr Passwort ändern.</p>
'.html_form('vmail_chpass', 'chpass.php', '', '<table>
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
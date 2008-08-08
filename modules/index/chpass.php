<?php
require_once('inc/debug.php');
require_once('inc/security.php');

$title = "Passwort ändern";
$error = '';

require_role(array(ROLE_SYSTEMUSER, ROLE_CUSTOMER));


if ($_POST['password1'] != '')
{
  check_form_token('index_chpass');
  $result = NULL;
  if ($_SESSION['role'] & ROLE_SYSTEMUSER)
    $result = find_role($_SESSION['userinfo']['uid'], $_POST['old_password']);
  else
    $result = find_role($_SESSION['customerinfo']['customerno'], $_POST['old_password']);

  if ($result == NULL)
    input_error('Das bisherige Passwort ist nicht korrekt!');
  elseif ($_POST['password2'] != $_POST['password1'])
    input_error('Die Bestätigung ist nicht identisch mit dem neuen Passwort!');
  elseif ($_POST['password2'] == '')
    input_error('Sie müssen das neue Passwort zweimal eingeben!');
  elseif ($_POST['old_password'] == '')
    input_error('Altes Passwort nicht angegeben!');
  elseif (($check = strong_password($_POST['password1'])) !== true)
    input_error("Das Passwort ist zu einfach (cracklib sagt: {$check})!");
  else
  {
    if ($result & ROLE_SYSTEMUSER)
      set_systemuser_password($_SESSION['userinfo']['uid'], $_POST['password1']);
    elseif ($result & ROLE_CUSTOMER)
      set_customer_password($_SESSION['customerinfo']['customerno'], $_POST['password1']);
    else
      system_failure("WTF?! (\$result={$result})");
    
    if (! $debugmode)
      header('Location: index');
    else
      output('');
  }
}



if ($_SESSION['role'] & ROLE_SYSTEMUSER)
  warning('Beachten Sie: Wenn Sie hier Ihr Passwort ändern, betrifft dies auch Ihr Anmelde-Passwort am Server (SSH).');

output('<h3>Passwort ändern</h3>
<p>Hier können Sie Ihr Passwort ändern.</p>
'.html_form('index_chpass', 'chpass', '', '<table>
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

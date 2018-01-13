<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2018 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('session/start.php');
require_once('vmail.php');
require_once('mailaccounts.php');

require_role(array(ROLE_VMAIL_ACCOUNT, ROLE_MAILACCOUNT));

$role = $_SESSION['role'];

title("Passwort ändern");



if (isset($_POST['password1']) && $_POST['password1'] != '')
{
  $accname = $_SESSION['mailaccount'];
  check_form_token('email_chpass');
  $result = find_role($accname, $_POST['old_password']);

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
    if ($role & ROLE_VMAIL_ACCOUNT)
    {
      DEBUG("Ändere VMail-Passwort");
      change_vmail_password($accname, $_POST['password1']);
    }
    elseif ($role & ROLE_MAILACCOUNT)
    {
      DEBUG("Ändere IMAP-Passwort");
      change_mailaccount(get_mailaccount_id($accname), array('password' => $_POST['password1']));
    }
    if (! $debugmode)
      header('Location: /');
    else
      output('');
  }
}



output('<p>Hier können Sie Ihr Passwort ändern.</p>
'.html_form('email_chpass', 'chpass', '', '<table>
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



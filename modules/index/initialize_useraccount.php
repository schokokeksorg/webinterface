<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2012 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

title("Passwort setzen");
$show = 'token';

if (isset($_REQUEST['uid']) and isset($_REQUEST['token']))
{
  $uid = (int) $_REQUEST['uid'];
  $token = $_REQUEST['token'];
  
  require_once('newpass.php');
  require_once('inc/security.php');
  if (validate_uid_token($uid, $token))
  {
    $show = 'agb';
    if ($_REQUEST['agb'] == '1') {
      $show = 'password';
    }
    if (isset($_POST['password']))
    {
      if ($_POST['password'] != $_POST['password2'])
        input_error("Die beiden Passwort-Eingaben stimmen nicht überein.");
      elseif ($_POST['password'] == '')
        input_error("Es kann kein leeres Passwort gesetzt werden");
      elseif (($result = strong_password($_POST['password'])) !== true)
        input_error("Das Passwort ist zu einfach (cracklib sagt: {$result})!");
      else
      {
        require_once('session/checkuser.php');
        require_once('inc/base.php');
        logger(LOG_INFO, "modules/index/initialize_useraccount", "initialize", "uid »{$uid}« set a new password");
        set_systemuser_password($uid, $_POST['password']);
        success_msg('Das Passwort wurde gesetzt!');
        invalidate_systemuser_token($uid);
        $_SESSION['role'] = find_role($uid, '', True);;
	setup_session($_SESSION['role'], $uid);
	title("Passwort gesetzt");
        output('<p>Ihr neues Passwort wurde gesetzt, Sie können jetzt '.internal_link('index', 'die Web-Oberfläche sofort benutzen').'.</p>');
        $show = NULL;
      }
    }
  }
  else
  {
    input_error("Der eingegebene Code war nicht korrekt. Eventuell haben Sie die Adresse nicht vollständig übernommen oder die Gültigkeit des Sicherheitscodes ist abgelaufen.");
  }
}

if ($show == 'password')
{
  title("Neues Passwort setzen");
  output('<p>Bitte legen Sie jetzt Ihr neues Passwort fest.</p>
  <p>Aufgrund einer technischen Einschränkung sollten Sie momentan auf Anführungszeichen (" und \') sowie auf Backslashes (\) im Passwort verzichten.</p>'.
  html_form('initialize_useraccount', '', '', '<p style="display: none"><input type="hidden" name="uid" value="'.$uid.'" />
  <input type="hidden" name="token" value="'.$token.'" /><input type="hidden" name="agb" value="1" /></p>
  <p><span class="login_label">Neues Passwort:</span> <input type="password" name="password" size="30" /></p>
  <p><span class="login_label">Bestätigung:</span> <input type="password" name="password2" size="30" /></p>
  <p><span class="login_label">&#160;</span> <input type="submit" value="Passwort setzen" /></p>
  '));
}
elseif ($show == 'agb')
{
  title("Bestätigung unserer AGB");
  output('<p>Die Nutzung unseres Angebots ist an unsere <a href="http://www.schokokeks.org/agb">Allgemeinen Geschäftsbedingungen</a> gebunden. Bitte lesen Sie diese Bedingungen und bestätigen Sie Ihr Einverständnis. Sollten Sie diese Bedingungen nicht akzeptieren, setzen Sie sich bitte mit uns in Verbindung.</p>'.
  html_form('initialize_useraccount_agb', '', '', '<p style="display: none"><input type="hidden" name="uid" value="'.$uid.'" />
  <input type="hidden" name="token" value="'.$token.'" /></p>
  <p><span class="login_label">&#160;</span><input type="checkbox" name="agb" value="1" /> Ja, ich akzeptiere die AGB.<p>
  <p><span class="login_label">&#160;</span> <input type="submit" value="Weiter" /></p>
  '));
}
elseif ($show == 'token')
{
  title("Neues Passwort setzen");
  output('<p>Bitte rufen Sie die Adresse aus Ihrer Begrüßungs-E-Mail auf um ein neues Passwort zu setzen.');
}


?>

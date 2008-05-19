<?php
$title = "Passwort setzen";

$show = 'token';

if (isset($_REQUEST['uid']) and isset($_REQUEST['token']))
{
  $uid = (int) $_REQUEST['uid'];
  $token = $_REQUEST['token'];
  
  require_once('newpass.php');
  require_once('inc/security.php');
  if (validate_uid_token($uid, $token))
  {
    $show = 'password';
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
        logger("modules/index/initialize_useraccount.php", "initialize", "uid »{$uid}« set a new password");
        set_systemuser_password($uid, $_POST['password']);
        success_msg('Das Passwort wurde gesetzt!');
        invalidate_systemuser_token($uid);
        $_SESSION['role'] = find_role($uid, '', True);;
	setup_session($_SESSION['role'], $uid);
        output('<h3>Passwort gesetzt</h3>
        <p>Ihr neues Passwort wurde gesetzt, Sie können jetzt <a href="index.php">die Web-Oberfläche sofort benutzen</a>.</p>');
        $show = NULL;
      }
    }
  }
  else
  {
    input_error("Der eingegebene Code war nicht korrekt. Bitte benutzen Sie die Kopieren & Einfügen-Operation!");
  }
}

if ($show == 'password')
{
  output('<h3>Neues Passwort setzen</h3>
  <p>Bitte legen Sie jetzt Ihr neues Passwort fest.</p>'.
  html_form('initialize_useraccount', '', '', '<p style="display: none"><input type="hidden" name="uid" value="'.$uid.'" />
  <input type="hidden" name="token" value="'.$token.'" /></p>
  <p><span class="login_label">Neues Passwort:</span> <input type="password" name="password" size="30" /></p>
  <p><span class="login_label">Bestätigung:</span> <input type="password" name="password2" size="30" /></p>
  <p><span class="login_label">&#160;</span> <input type="submit" value="Passwort setzen" /></p>
  '));
}
elseif ($show == 'token')
{
  output('<h3>Neues Passwort setzen</h3>
  <p>Bitte rufen Sie die Adresse aus Ihrer Begrüßungs-E-Mail auf um ein neues Passwort zu setzen.');
}


?>
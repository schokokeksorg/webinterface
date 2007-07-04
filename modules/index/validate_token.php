<?php
$title = "Passwort beantragen";

$show = 'token';

if (isset($_REQUEST['customerno']) and isset($_REQUEST['token']))
{
  $customerno = (int) $_REQUEST['customerno'];
  $token = $_REQUEST['token'];
  
  require_once('newpass.php');
  require_once('inc/security.php');
  if (validate_token($customerno, $token))
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
        logger("modules/index/validate_token.php", "pwrecovery", "customer »{$customerno}« set a new password");
        set_customer_password($customerno, $_POST['password']);
        success_msg('Das Passwort wurde gesetzt!');
        invalidate_customer_token($customerno);
        $_SESSION['role'] = ROLE_CUSTOMER;
        $_SESSION['customerinfo'] = get_customer_info($customerno);
        output('<h3>Passwort gesetzt</h3>
        <p>Ihr neues Passwort wurde gesetzt, Sie können jetzt <a href="index.php">die Web-Oberfläche sofort benutzen</a>.</p>');
        $show = NULL;
      }
    }
  }
  else
  {
    input_error("Der eingegebene Code war nicht korrekt. Bitte benutzen Sie die Kopieren &amp; Einfügen-Operation!");
  }
}

if ($show == 'password')
{
  output('<h3>Neues Passwort setzen</h3>
  <p>Bitte legen Sie jetzt Ihr neues Kunden-Passwort fest.</p>
  <form action="" method="post">
  <input type="hidden" name="customerno" value="'.$customerno.'" />
  <input type="hidden" name="token" value="'.$token.'" />
  <p><span class="login_label">Neues Passwort:</span> <input type="password" name="password" size="30" /></p>
  <p><span class="login_label">Bestätigung:</span> <input type="password" name="password2" size="30" /></p>
  <p><span class="login_label">&nbsp;</span> <input type="submit" value="Passwort setzen" />
  </form>');
}
elseif ($show == 'token')
{
  output('<h3>Neues Passwort setzen</h3>
  <p>Bitte geben Sie Ihre Kundennummer und den per E-Mail zugeschickten Code ein. Alternativ können sie den Link aus der E-Mail direkt aufrufen.</p>
  <form action="" method="post">
  <p><span class="login_label">Kundennummer:</span> <input type="text" name="customerno" size="30" /></p>
  <p><span class="login_label">Code:</span> <input type="text" name="token" size="30" /></p>
  <p><span class="login_label">&nbsp;</span> <input type="submit" value="Überprüfen" />
  </form>');
}


?>

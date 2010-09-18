<?php
title("Neues Passwort beantragen");

//require_once('inc/error.php');
//system_failure("Diese Funktion ist noch nicht fertiggestellt.");

if (isset($_POST['customerno']))
{
  require_once('newpass.php');
  if (customer_has_email($_POST['customerno'], $_POST['email']))
  {
    if (create_token($_POST['customerno']))
    {
      require_once('mail.php');
      require_once('inc/base.php');
      send_customer_token($_POST['customerno']);
      logger(LOG_INFO, "modules/index/new_password", "pwrecovery", "token sent for customer »{$_POST['customerno']}«");
      success_msg('Die angegebenen Daten waren korrekt, Sie sollten umgehend eine E-Mail erhalten.');
    }
  }
  else
  {
    input_error("Die eingegebenen Daten waren nicht korrekt. Sollten Sie nicht mehr wissen, welche E-Mail-Adresse Sie angegeben haben, wenden Sie sich bitte an einen Administrator.");
  }
}

output('<p>Sofern Sie bei Ihrer Anmeldung noch kein Passwort für Ihren Kundenaccount festgelegt hatten, können Sie hier ein neues Passwort festlegen. Sie müssen dafür Ihre Kundennummer und die bei der Anmeldung angegebene E-Mail-Adresse eingeben.</p>
<p>Nach dem Ausfüllen dieses Formulars erhalten Sie eine E-Mail mit einem Link, den Sie in Ihrem Browser öffnen müssen. Dort können Sie dann ein neues Passwort eingeben.</p>
<form action="" method="post">
<p><span class="login_label">Kundennummer:</span> <input type="text" name="customerno" size="30" /></p>
<p><span class="login_label">E-Mail-Adresse:</span> <input type="text" name="email" size="30" /></p>
<p><span class="login_label">&#160;</span> <input type="submit" value="Passwort anfordern" /></p>
</form>');



?>

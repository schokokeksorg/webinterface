<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2013 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

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

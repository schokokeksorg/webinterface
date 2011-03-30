<?php
require_once("includes/newsletter.php");
require_once("inc/security.php");
require_once("inc/base.php");


if ($_POST['newsletter'] == 'no' || $_POST['recipient'] == "") {
  $sure = user_is_sure();
  if ($sure === NULL)
  {
    check_form_token('newsletter');
    are_you_sure("newsletter=no", "Wenn Sie keinen Newsletter abonnieren, erhalten Sie von uns keine Informationen zu laufenden Änderungen bei schokokeks.org. Beachten Sie bitte dennoch regelmäßig die Einträge auf dieser Website, unser Weblog und unsere Status-Seite. Möchten Sie den Newsletter wirklich abbestellen?");
  }
  elseif ($sure === true)
  {
    set_newsletter_address(NULL);
    if (! $debugmode)
      header('Location: newsletter');
  }
  elseif ($sure === false)
  {
    if (! $debugmode)
      header('Location: newsletter');
  }
} else {
  check_form_token('newsletter');
  if (! check_emailaddr($_POST['recipient']) || filter_input_general($_POST['recipient']) != $_POST['recipient']) {
    system_failure("Keine gültige E-Mail-Adresse!");
  }
  set_newsletter_address($_POST['recipient']);
  if (! $debugmode)
    header('Location: newsletter');
}



<?php
/*

  Session-Start-Script wird vom dispatcher eingebunden

*/

require_once('session/checkuser.php');
require_once('inc/error.php');
require_once('inc/debug.php');

if (!session_start())
        system_failure('Die Sitzung konnte nicht gestartet werden, bitte benachrichtigen Sie den Administrator!');


DEBUG("<pre>POST-DATA: ".htmlspecialchars(print_r($_POST, true))."\nSESSION_DATA: ".htmlentities(print_r($_SESSION, true))."</pre>");

if (isset($_POST['username']) && isset($_POST['password']))
{
  $role = find_role($_POST['username'], $_POST['password']);
  if ($role === NULL)
  {
    $_SESSION['role'] = ROLE_ANONYMOUS;
    login_screen('Ihre Anmeldung konnte nicht durchgeführt werden. Vermutlich haben Sie falsche Zugangsdaten eingegeben.');
  }
  else
  {
    session_regenerate_id();
    $_SESSION['role'] = $role;

    switch ($role)
    {
    case ROLE_SYSTEMUSER:
      $info = get_user_info($_POST['username']);
      $_SESSION['userinfo'] = $info;
      break;
    case ROLE_CUSTOMER:
      $info = get_customer_info($_POST['username']);
      $_SESSION['customerinfo'] = $info;
      break;
    }
  }
  unset($_POST['username']);
  unset($_POST['password']);
}

elseif (isset($_SESSION['role']))
{
  /* User ist eingeloggt (aber vielleicht als ROLE_ANONYMOUS!) */
}

else
{
  $_SESSION['role'] = ROLE_ANONYMOUS;
}
// Wenn wir hier sind, ist der Benutzer eingeloggt. Möglicherweise nur als ANONYMOUS

?>

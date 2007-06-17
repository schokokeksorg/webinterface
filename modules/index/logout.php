<?php

require_once('session/start.php');

require_once('inc/error.php');

logger("modules/index/logout.php", "logout", "logged out");

if (!session_destroy())
{
  logger("modules/index/logout.php", "logout", "session timed out.");
	system_failure('Die Sitzung konnte nicht geschlossen werden, eventuell ist die Wartezeit abgelaufen und die Sitzung wurde daher schon beendet.');
}
unset($_SESSION['role']);

output('

<h3>Abmeldung</h3>

<p>Sie wurden vom System abgemeldet.</p>

<p>Um sich neu anzumelden, klicken Sie bitte hier: <a href="index.php">Anmeldung</a>.</p>

');



?>

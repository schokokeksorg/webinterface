<?php

/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('session/start.php');

require_once('inc/error.php');

if (isset($_COOKIE['CLIENTCERT_AUTOLOGIN'])) {
    setcookie('CLIENTCERT_AUTOLOGIN', false, 0, '/');
}
if (!session_destroy()) {
    logger(LOG_INFO, "modules/index/logout", "logout", "session timed out.");
    system_failure('Die Sitzung konnte nicht geschlossen werden, eventuell ist die Wartezeit abgelaufen und die Sitzung wurde daher schon beendet.');
}
$_SESSION['role'] = ROLE_ANONYMOUS;

logger(LOG_INFO, "modules/index/logout", "logout", "logged out");

title("Abmeldung");
output('

<p>Sie wurden vom System abgemeldet.</p>

<p>Um sich neu anzumelden, klicken Sie bitte hier: ' . internal_link("index", "Anmeldung") . '.</p>

');

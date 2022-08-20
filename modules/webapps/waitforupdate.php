<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/base.php');

require_role(ROLE_SYSTEMUSER);

title('Update wird ausgeführt');
$section = 'webapps_freewvs';

output('<p>Sie haben ein Update Ihrer Web-Anwendung in Autrag gegeben. Dieses Update wird in Kürze automatisiert ausgeführt.
Sie erhalten dazu eine E-Mail-Bestätigung über den Erfolg oder Misserfolg des automatischen Updates. <strong>Dies dauert bis zu 15 Minuten!</strong></p>

<p>Für den Fall einer Fehlfunktion des autoamtischen Updates werden vorab Sicherheitskopien Ihrer alten Dateien und der verwendeten Datenbanken erzeugt. Diese finden Sie unter <strong>~/backup/</strong> in Ihrem Home-Verzeichnis. Wenn die neue Version einige Zeit ohne Beanstandungen funktioniert, sollten Sie die alten Backups löschen um Speicherplatz zu sparen.</p>');

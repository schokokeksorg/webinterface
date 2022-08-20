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
require_once('session/start.php');

require_role(ROLE_SYSTEMUSER);

title('OwnCloud einrichten');
$section = 'webapps_install';

// Wurde beim Schreiben in die Session schon verifiziert
$docroot = $_SESSION['webapp_docroot'];
$url = $_SESSION['webapp_url'];

if (! $docroot) {
    system_failure('Kann die Session-Daten nicht auslesen. So geht das nicht.');
}


if (isset($_POST['submit'])) {
    require_once('webapp-installer.php');

    check_form_token('install_owncloud');

    create_new_webapp('owncloud', $docroot, $url, '');

    warning('Beachten Sie bitte, dass der erste Besucher Ihrer neuen Owncloud-Instanz den Namen und das Passwort des Administrators festlegen kann. Führen Sie die Inbetriebnahme daher bitte zeitnah durch!');
    title("OwnCloud wird installiert");
    output('<p>Ihre OwnCloud wird in Kürze installiert. Sie erhalten eine E-Mail, sobald die Anwendung betriebsbereit ist.</p>
');
} else {
    require_once('modules/vhosts/include/vhosts.php');
    $vhosts = list_vhosts();

    $form = '
<h4>Basisdaten</h4>
<div style="margin-left: 2em;">
  <h5>Speicherort</h5>
  <p>Die Cloud wird im Verzeichnis <strong>'.$docroot.'</strong> installiert und wird später voraussichtlich unter <strong>'.$url.'</strong> abrufbar sein.</p>
  <p>Beachten Sie bitte: Die Installation wird in Ihrem Home-Verzeichnis durchgeführt und es wird ein normaler Host im Webinterface dafür angelegt. Sie können diese Einstellungen also jederzeit verändern.</p>
  
  <h4>Administrator-Konto</h4>
  <p>Der Benutzername und das Passwort des Administrator-Benutzers werden beim ersten Besuch Ihrer neuen OwnCloud festgelegt.</p>

</div>

<p><input type="submit" name="submit" value="OwnCloud installieren!" /></p>
';

    output(html_form('install_owncloud', '', '', $form));
}

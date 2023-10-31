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

title('Drupal 7 einrichten');
$section = 'webapps_install';

// Wurde beim Schreiben in die Session schon verifiziert
$docroot = $_SESSION['webapp_docroot'];
$url = $_SESSION['webapp_url'];

if (!$docroot) {
    system_failure('Kann die Session-Daten nicht auslesen. So geht das nicht.');
}


if (isset($_POST['submit'])) {
    require_once('install-drupal7.php');
    require_once('webapp-installer.php');

    check_form_token('install_drupal7');

    $data = validate_data($_POST);
    if (!$data) {
        system_failure('wtf?!');
    }
    create_new_webapp('drupal7', $docroot, $url, $data);

    title("Drupal wird installiert");
    output('<p>Ihre Drupal-Website wird jetzt installiert. Sie erhalten eine E-Mail, sobald die Website betriebsbereit ist.</p>
');
} else {
    require_once('modules/vhosts/include/vhosts.php');
    $vhosts = list_vhosts();

    output('<p>Die Einrichtung von Drupal erfordert die Angabe ein paar weniger Daten.</p>');

    $form = '
<h4>Basisdaten</h4>
<div style="margin-left: 2em;">
  <h5>Ort der neuen Website</h5>
  <p>Drupal wird im Verzeichnis <strong>' . $docroot . '</strong> installiert und wird später voraussichtlich unter <strong>' . $url . '</strong> abrufbar sein.</p>
  <p>Beachten Sie bitte: Die Installation wird in Ihrem Home-Verzeichnis durchgeführt und es wird ein normaler Host im Webinterface dafür angelegt. Sie können diese Einstellungen also jederzeit verändern. Auch die dafür angelegte Datenbank können Sie jederzeit selbst verwalten oder löschen.</p>
  
  <h5>Name der Seite</h5>
  <p>Der Seitenname wird im Titel jeder Seite angezeigt und für diverse Texte verwendet.</p>
  <p><label for="sitename">Seiten-Name:</label> <input type="text" id="sitename" name="sitename" /></p>
  
  <h5>E-Mail-Adresse der Seite</h5>
  <p>Wenn die Website E-Mails versendet (z.B. für neue Benutzer, bei kommentaren auf einzelnen Seiten, ...) erscheint diese Adresse als Absender.
  <p><label for="siteemail">E-Mail-Adresse:</label> <input type="text" id="siteemail" name="siteemail" value="' . $_SESSION['userinfo']['username'] . '@' . config('masterdomain') . '" /></p>
</div>

<h4>Drupal-Administrator</h4>
<div style="margin-left: 2em;">
  <p>Der Administrator kann später auf der Website neue Benutzer anlegen, Seiten erzeugen und verändern und sonstige Verwaltungsaufgaben durchführen.</p>
  <p><label for="adminuser">Benutzername:</label> <input type="text" id="adminuser" name="adminuser" value="' . $_SESSION['userinfo']['username'] . '" /></p>
  <p><label for="adminpassword">Passwort:</label> <input type="password" id="adminpassword" name="adminpassword" /></p>
  <p><label for="adminemail">E-Mail-Adresse:</label> <input type="text" id="adminemail" name="adminemail" value="' . $_SESSION['userinfo']['username'] . '@' . config('masterdomain') . '" /></p>
</div>

<p><input type="submit" name="submit" value="Drupal installieren!" /></p>
';

    output(html_form('install_drupal7', '', '', $form));
}

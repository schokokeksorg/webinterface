<?php

require_once('inc/base.php');
require_once('session/start.php');

require_role(ROLE_SYSTEMUSER);

title('OwnCloud einrichten');
$section = 'webapps_install';

// Wurde beim Schreiben in die Session schon verifiziert
$docroot = $_SESSION['webapp_docroot'];
$url = $_SESSION['webapp_url'];

if (! $docroot)
{
  system_failure('Kann die Session-Daten nicht auslesen. So geht das nicht.');
}


if (isset($_POST['submit']))
{
  require_once('install-owncloud.php');
  require_once('webapp-installer.php');
  
  check_form_token('install_owncloud');

  $data = validate_data($_POST);
  if (! $data)
    system_failure('wtf?!');
  create_new_webapp('owncloud', $docroot, $url, $data); 
  
  title("OwnCloud wird installiert");
  output('<p>Ihre OwnCloud wird in Kürze installiert. Sie erhalten eine E-Mail, sobald die Anwendung betriebsbereit ist.</p>
');
  
}
else
{

  require_once('modules/vhosts/include/vhosts.php');
  $vhosts = list_vhosts();
  
  $form = '
<h4>Basisdaten</h4>
<div style="margin-left: 2em;">
  <h5>Speicherort</h5>
  <p>Die Cloud wird im Verzeichnis <strong>'.$docroot.'</strong> installiert und wird später voraussichtlich unter <strong>'.$url.'</strong> abrufbar sein.</p>
  <p>Beachten Sie bitte: Die Installation wird in Ihrem Home-Verzeichnis durchgeführt und es wird ein normaler Host im Webinterface dafür angelegt. Sie können diese Einstellungen also jederzeit verändern.</p>
  
  <h5>Administrator</h5>
  <p>Ihre OwnCloud wird mit lediglich einem Administrator-benutzer installiert. Der Benutzername ist <strong>admin</strong>.</p>
  <p><label for="adminpass">Administrator-Passwort:</label> <input type="password" id="adminpass" name="adminpass" /></p>
</div>

<p><input type="submit" name="submit" value="OwnCloud installieren!" /></p>
';

  output(html_form('install_owncloud', '', '', $form));

}


<?php

require_once('inc/base.php');
require_once('session/start.php');

global $config;


require_role(ROLE_SYSTEMUSER);

$title = 'MediaWiki einrichten';
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
  require_once('install-mediawiki.php');
  require_once('webapp-installer.php');
  
  check_form_token('install_mediawiki');

  $data = validate_data($_POST);
  if (! $data)
    system_failure('wtf?!');
  create_new_webapp('mediawiki', $docroot, $url, $data); 
  
  output('<h2>MediaWiki installieren</h2>

<p>Ihr MediaWiki wird jetzt installiert. Sie erhalten eine E-Mail, sobald das Wiki betriebsbereit ist.</p>
');
  
}
else
{

  require_once('modules/vhosts/include/vhosts.php');
  $vhosts = list_vhosts();
  
  output('<h3>MediaWiki installieren</h3>

<p>Die Einrichtung von MediaWiki erfordert die Angabe ein paar weniger Daten.</p>
');

  $form = '
<h4>Basisdaten</h4>
<div style="margin-left: 2em;">
  <h5>Ort des neuen Wikis</h5>
  <p>Das Wiki wird im Verzeichnis <strong>'.$docroot.'</strong> installiert und wird später voraussichtlich unter <strong>'.$url.'</strong> abrufbar sein.</p>
  <p>Beachten Sie bitte: Die Installation wird in Ihrem Home-Verzeichnis durchgeführt und es wird ein normaler Host im Webinterface dafür angelegt. Sie können diese Einstellungen also jederzeit verändern.</p>
  
  <h5>Name des Wikis</h5>
  <p>Jedes MediaWiki benötigt einen griffigen Namen. Der Name kann entweder in »WikiSchreibweise« (zusammengezogene Wörter mit großgeschriebenen Anfangsbuchstaben) oder in normaler Schreibweise sein.</p>
  <p><label for="wikiname">Wiki-Name:</label> <input type="text" id="wikiname" name="wikiname" /></p>
</div>

<h4>Wiki-Administrator</h4>
<div style="margin-left: 2em;">
  <p>Der Wiki-Administrator kann später im Wiki neue Benutzer anlegen, Seiten sperren oder sonstige Verwaltungsaufgaben durchführen.</p>
  <p><label for="adminuser">Benutzername:</label> <input type="text" id="adminuser" name="adminuser" value="WikiSysop" /></p>
  <p><label for="adminpassword">Passwort:</label> <input type="password" id="adminpassword" name="adminpassword" /></p>
  <p><label for="adminemail">E-Mail-Adresse:</label> <input type="text" id="adminemail" name="adminemail" value="'.$_SESSION['userinfo']['username'].'@'.$config['masterdomain'].'" /></p>
</div>

<p><input type="submit" name="submit" value="Wiki installieren!" /></p>
';

  output(html_form('install_mediawiki', '', '', $form));

}


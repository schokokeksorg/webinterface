<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2014 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/base.php');
require_once('session/start.php');

require_once('modules/vhosts/include/vhosts.php');

require_role(ROLE_SYSTEMUSER);

title('Web-Anwendung installieren');

output('<p>Richten Sie hiermit unkompliziert eine neue Web-Anwendung ein. Sie können dafür entweder eine vorhandene Domain bzw. Subdomain benutzen oder eine neue anlegen.</p>
<p>Wählen Sie bitte auf dieser Seite aus, welche Anwendung Sie installieren möchten und unter welcher Domain/Subdomain dies geschehen soll. Nach dieser Seite werden noch ein paar Details zur betreffenden Anwendung erfasst.</p>
');


$form = '<h4>Anwendung auswählen</h4>
<div style="margin-left: 2em;">
  <p><input checked="checked" type="radio" name="application" id="application_mediawiki" value="mediawiki" /> <label for="application_mediawiki">MediaWiki</label></p>
  <p style="margin-left: 2em;">MediaWiki ist eine Wiki-Software, mit der Seiteninhalte von Besuchern geändert werden können. MediaWiki ist u.A. die Grundlage der Online-Enzyklopädie »Wikipedia«.</p>
  <p><input type="radio" name="application" id="application_drupal7" value="drupal7" /> <label for="application_drupal7">Drupal (Version 7.x)</label></p>
  <p style="margin-left: 2em;">Drupal ist ein verbreitetes Content-Management-System. Als solches bietet es die Möglichkeit, Seiten-Inhalte im Web-Browser zu ändern, einzelne Seiten nur angemeldeten Benutzern freizugeben oder z.B. das Kommentieren einzelner Seiten.</p>
  <p><input type="radio" name="application" id="application_owncloud" value="owncloud" /> <label for="application_owncloud">OwnCloud</label></p>
  <p style="margin-left: 2em;">Mit OwnCloud können Sie Ihre Dateien, Adressbücher oder Kalenderdaten zentral ablegen und von überall nutzen.</p>
</div>
';

$form .= '<h4>Installationsort:</h4>
<div style="margin-left: 2em;">
  <p><input type="radio" name="target" value="new" id="radio_new" checked="checked" />&#160;<label for="radio_new">Neue Domain/Subdomain erstellen</label></p>
<div style="margin-left: 2em;">
<h5>Name</h5>
';
$form .= "<div style=\"margin-left: 2em;\"><input type=\"text\" name=\"hostname\" id=\"hostname\" size=\"10\" onkeyup=\"document.getElementById('radio_new').checked=true\" /><strong>.</strong>".domainselect('', 'onchange="document.getElementById(\'radio_new\').checked=true"');
$form .= "<br /><input type=\"checkbox\" name=\"options[]\" id=\"aliaswww\" value=\"aliaswww\" /> <label for=\"aliaswww\">Auch mit <strong>www</strong> davor.</label></div>";
$form .= "
    <h5>SSL-Verschlüsselung</h5>
    <div style=\"margin-left: 2em;\">
    <select name=\"ssl\" id=\"ssl\">
      <option value=\"none\" selected=\"selected\">SSL optional anbieten</option>
      <option value=\"forward\">Immer auf SSL umleiten</option>
    </select>
    </div>
    <h5>Logfiles</h5>
    <div style=\"margin-left: 2em;\">
      <select name=\"logtype\" id=\"logtype\">
        <option value=\"none\">keine Logfiles</option>
        <option value=\"anonymous\">anonymisiert</option>
        <option value=\"default\">vollständige Logfile</option>
      </select><br />
      <input type=\"checkbox\" id=\"errorlog\" name=\"errorlog\" value=\"1\" />&#160;<label for=\"errorlog\">Fehlerprotokoll (error_log) einschalten</label>
    </div>
</div>
</div>
";

$vhosts = list_vhosts();

$options = array();
foreach ($vhosts AS $vhost)
{
  $options[$vhost['docroot']] = $vhost['fqdn'];
}

$form .= '
<div style="margin-left: 2em;">
  <p><input type="radio" name="target" value="vhost" id="radio_vhost" />&#160;<label for="radio_vhost">Vorhandene Domain/Subdomain benutzen</label></p>
  <div style="margin-left: 2em;">
  '.html_select('vhost', $options, '', 'onchange="document.getElementById(\'docroot\').firstChild.textContent=document.getElementById(\'vhost\').options.item(document.getElementById(\'vhost\').options.selectedIndex).value ; document.getElementById(\'radio_vhost\').checked=true"').'
  <p>Datei-Verzeichnis: <strong id="docroot">'.$vhosts[0]['docroot'].'</strong></p>
  <p>Beachten Sie bitte: Die Installation wird nur funktionieren, wenn das Verzeichnis dieser Domain bzw. Subdomain leer oder nicht vorhanden ist. Sofern Sie dort bereits eigene Dateien hinterlegt haben oder eine andere Web-Anwendung installiert ist, wird die Installation nicht durchgeführt.</p>
  </div>
</div>

<p><input type="submit" name="submit" value="Weiter" /></p>
';

output(html_form('webapp_install', 'save.php', '', $form));




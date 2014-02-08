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

include('ftpusers.php');

require_once('inc/base.php');
require_role(ROLE_SYSTEMUSER);

$section='ftpusers_accounts';

$ftpuser = empty_ftpuser();

if (isset($_GET['id']))
  $ftpuser = load_ftpuser($_GET['id']);

if ($ftpuser['username'])
  title('Ändern des FTP-Benutzers');
else
{
  title('Neuer FTP-Zugang');
  output('<p style="border: 2px solid red; padding: 1em; padding-left: 4em;"><img src="'.$prefix.'images/warning.png" style="margin-left: -3em; float: left;" /><strong>Bitte beachten Sie:</strong> Ein FTP-Benutzer kann nur im hier angegebenen Verzeichnis (und dallen darin enthaltenen Verzeichnissen) Dateien erstellen oder ändern. Sofern der Benutzer allerdings die Möglichkeit hat, PHP- oder CGI-Programme zu installieren und über den Webserver aufzurufen, kann er damit auch außerhalb dieses Verzeichnisses agieren. Schalten Sie bitte ggf. die PHP- und CGI-Unterstützung für die betreffende Website aus.</p>');
}

$username = substr($ftpuser['username'], strlen($_SESSION['userinfo']['username'])+1);

$user_home = $_SESSION['userinfo']['homedir'];
$homedir = substr($ftpuser['homedir'], strlen($user_home)+1);
DEBUG($user_home.' / '.$homedir.' / '.$ftpuser['homedir']);

$active = ($ftpuser['active'] == 1 ? 'checked="checked" ' : '');
$forcessl = ($ftpuser['forcessl'] == 1 ? 'checked="checked" ' : '');

$servers = server_names();
$available_servers = array_merge(array(my_server_id()), additional_servers());

$whichserver = '<strong>'.$servers[my_server_id()].'</strong>';
if (count($available_servers) > 1)
{
  $serverselect = array();
  foreach ($available_servers AS $s)
    $serverselect[$s] = $servers[$s];
  $whichserver = html_select('server', $serverselect, $ftpuser['server']);
}
  


output(html_form('ftpusers_edit', 'save', 'id='.$ftpuser['id'], '
  <table style="margin-bottom: 1em;">
  <tr>
    <td>Benutzername:</td>
    <td><strong>'.$_SESSION['userinfo']['username'].'-</strong><input type="text" name="ftpusername" id="ftpusername" value="'.$username.'" /></td>
  </tr>
  <tr>
    <td>Verzeichnis:</td>
    <td><strong>'.$user_home.'/</strong><input type="text" id="homedir" name="homedir" value="'.$homedir.'" /></td>
  </tr>
  <tr>
    <td>Passwort:</td>
    <td><input type="password" id="password" name="password" value="" /></td>
  </tr>
  <tr>
    <td>Zugang aktivieren:</td>
    <td><input type="checkbox" id="active" name="active" value="1" '.$active.'/> auf Server '.$whichserver.'<br/><input type="checkbox" id="forcessl" name="forcessl" value="1" '.$forcessl.'/>&#160;<label for="forcessl">SSL-Verschlüsselung erforderlich<sup>*</sup></label></td>
  </tr>
  </table>
  <p><input type="submit" name="save" value="Speichern" /></p>
  
  <p><sup>*</sup>) Wenn die Verschlüsselung nicht erforderlich ist, können Sie mit diesen Zugangsdaten eine ungesicherte Verbindung auf TCP-Port 1021 aufbauen. Auf dem Standard-Port 21 wird grundsätzlich eine Verschlüsselung benötigt.</p>
'));







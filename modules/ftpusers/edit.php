<?php

include('ftpusers.php');

require_once('inc/base.php');
require_role(ROLE_SYSTEMUSER);

$title="FTP-Benutzer einrichten";
$section='ftpusers_accounts';

$ftpuser = empty_ftpuser();

if (isset($_GET['id']))
  $ftpuser = load_ftpuser($_GET['id']);

if ($ftpuser['username'])
  output('<h3>Ändern des FTP-Benutzers</h3>');
else
{
  output('<h3>Neuer FTP-Zugang</h3>');
  output('<p style="border: 2px solid red; padding: 1em; padding-left: 4em;"><img src="'.$prefix.'images/warning.png" style="margin-left: -3em; float: left;" /><strong>Bitte beachten Sie:</strong> Ein FTP-Benutzer kann nur im hier angegebenen Verzeichnis (und dallen darin enthaltenen Verzeichnissen) Dateien erstellen oder ändern. Sofern der Benutzer allerdings die Möglichkeit hat, PHP- oder CGI-Programme zu installieren und über den Webserver aufzurufen, kann er damit auch außerhalb dieses Verzeichnisses agieren. Schalten Sie bitte ggf. die PHP- und CGI-Unterstützung für die betreffende Website aus.</p>');
}

$username = substr($ftpuser['username'], strlen($_SESSION['userinfo']['username'])+1);

$user_home = $_SESSION['userinfo']['homedir'];
$homedir = substr($ftpuser['homedir'], strlen($user_home)+1);
DEBUG($user_home.' / '.$homedir.' / '.$ftpuser['homedir']);

$checked = ($ftpuser['active'] == 1 ? 'checked="checked" ' : '');

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
    <td><input type="checkbox" id="active" name="active" value="1" '.$checked.'/> auf Server '.$whichserver.'</td>
  </tr>
  </table>
  <p><input type="submit" name="save" value="Speichern" /></p>
  '));







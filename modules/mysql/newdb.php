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

require_once('session/start.php');
require_once('inc/icons.php');
require_role(array(ROLE_SYSTEMUSER));

global $prefix;

require_once('mysql.php');

$dbs = get_mysql_databases($_SESSION['userinfo']['uid']);
$users = get_mysql_accounts($_SESSION['userinfo']['uid']);
$username = $_SESSION['userinfo']['username'];

$section = 'mysql_overview';
title('Neue MySQL-Datenbank');

$dbnames = array();
foreach ($dbs as $db) {
  $dbnames[] = $db['name'];
}

$suggestion = $username.'_1';
$count = 2;
while (in_array($suggestion, $dbnames)) {
  $suggestion = $username.'_'.$count;
  $count++;
}

$form = '<h4>Name der neuen Datenbank</h4>
<input type="text" name="newdb" value="'.$suggestion.'" />
<p>Bitte nur Kleinbuchstaben, Zahlen und Unterstrich verwenden. Der Datenbankname muss mit dem Benutzernamen beginnen.</p>
<p><label for="description">Optionale Beschreibung dieser Datenbank:</label> <input type="text" name="description" id="description" /></p>
';
if (count(additional_servers()) > 0) {
  $form .= '<h4>Server</h4>';
  $form .= '<p>Auf welchem Server soll diese Datenbank eingerichtet werden?</p>';
  $available_servers = additional_servers();
  $available_servers[] = my_server_id();
  $available_servers = array_unique($available_servers);
  
  $selectable_servers = array();
  $all_servers = server_names();
  foreach ($all_servers as $id => $fqdn) {
    if (in_array($id, $available_servers)) {
      $selectable_servers[$id] = $fqdn;
    }
  }
  $form .= html_select('server', $selectable_servers, my_server_id());
  $form .= '<p>Alle Benutzer die auf diese Datenbank zugreifen dürfen, werden automatisch auf dem passenden Server eingerichtet</p>';
}
if (count($users) > 0) {
  $form .= '<h4>Berechtigungen</h4>';
  $form .= '<p>Welche der bisher vorhandenen Datenbank-Benutzer dürfen auf diese Datenbank zugreifen?</p>';
  foreach ($users as $user) {
    $form .= '<p><input type="checkbox" id="access_'.$user['username'].'" name="access[]" value="'.$user['username'].'" /> <label for="access_'.$user['username'].'">'.$user['username'].'</label></p>';
  }
}
 
$form .= '<p><input type="submit" name="submit" value="Speichern"/><p>';


output(html_form('mysql_newdb', 'save', 'action=newdb', $form));


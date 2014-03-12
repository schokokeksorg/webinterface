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
title('Neuer MySQL-Benutzer');


html_header('
<script type="text/javascript">

  function makePasswd() {
    var passwd = \'\';
    var chars = \'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789\';
    for (i=0; i<15; i++) {
      var c = Math.floor(Math.random()*chars.length + 1);
      passwd += chars.charAt(c)
    }
    return passwd;
  }

  function setRandomPassword() 
  {
    pass = makePasswd();
    document.getElementById(\'newpass\').value = pass;
    document.getElementById(\'newpass_display\').value = pass;
    document.getElementById(\'newpass_display\').parentNode.style.display = \'block\';
  }
</script>');


$usernames = array();
foreach ($users as $user) {
  $usernames[] = $user['username'];
}

$suggestion = $username;
$count = 1;
while (in_array($suggestion, $usernames)) {
  $suggestion = $username.'_'.$count;
  $count++;
}

$hint = 'Der MySQL-Benutzername muss entweder <strong>'.$username.'</strong> lauten oder mit <strong>'.$username.'_</strong> beginnen.';
if (in_array($username, $usernames)) {
  $hint = 'Der MySQL-Benutzername muss mit <strong>'.$username.'_</strong> beginnen.';
}


$form = '<h4>Benutzername</h4>
<input type="text" name="newuser" value="'.$suggestion.'" maxlength="16" />
<p>Bitte nur Kleinbuchstaben, Zahlen und Unterstrich verwenden. '.$hint.'</p>
<p>Aufgrund einer Einschränkung des MySQL-Servers dürfen Benutzernamen nur maximal 16 Zeichen lang sein.</p>
<p><label for="description">Optionale Beschreibung dieses Benutzers:</label> <input type="text" name="description" id="description" /></p>
<h4>Passwort</h4>
<p><input onchange="document.getElementById(\'newpass_display\').parentNode.style.display=\'none\'" type="password" name="newpass" id="newpass" value="" /> <button type="button" onclick="setRandomPassword()">Passwort erzeugen</button></p>
<p style="display: none;">Automatisch erzeugtes Passwort: <input id="newpass_display" type="text" readonly="readonly" /></p>
<h4>Berechtigungen</h4>';
if (count($dbs) > 0) {
  $form .= '<p>Auf welche der bisher vorhandenen Datenbanken darf dieser Benutzer zugreifen?</p>';
  foreach ($dbs as $db) {
    $desc = '';
    if ($db['description']) {
      $desc = ' - <em>'.$db['description'].'</em>';
    }
    $form .= '<p><input type="checkbox" id="access_'.$db['name'].'" name="access[]" value="'.$db['name'].'" /> <label for="access_'.$db['name'].'">'.$db['name'].$desc.'</label></p>';
  }
} else {
  $form .= '<p><em>Bisher gibt es noch keine Datenbanken.</em></p>';
}
 
$form .= '<p><input type="submit" name="submit" value="Speichern"/><p>';


output(html_form('mysql_newuser', 'save', 'action=newuser', $form));


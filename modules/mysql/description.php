<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2013 by schokokeks.org Hosting, namely
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
title('Beschreibung ändern');

if (isset($_GET['db'])) {
  $thisdb = NULL;
  foreach ($dbs as $db) {
    if ($db['name'] == $_GET['db']) {
      $thisdb = $db;
    }
  }
  $form = '<p>Ändern Sie hier die Beschreibung der Datenbank <strong>'.$thisdb['name'].'</strong>.</p>';
  $form .= '<p><input type="text" name="description" value="'.$thisdb['description'].'" /></p>
<p><input type="submit" value="Speichern" /></p>';
  output(html_form('mysql_description', 'save', "action=description&db={$thisdb['name']}", $form));
}
if (isset($_GET['username'])) {
  $thisuser = NULL;
  foreach ($users as $user) {
    if ($user['username'] == $_GET['username']) {
      $thisuser = $user;
    }
  }
  $form = '<p>Ändern Sie hier die Beschreibung der Datenbank <strong>'.$thisuser['username'].'</strong>.</p>';
  $form .= '<p><input type="text" name="description" value="'.$thisuser['description'].'" /></p>
<p><input type="submit" value="Speichern" /></p>';
  output(html_form('mysql_description', 'save', "action=description&username={$thisuser['username']}", $form));
}




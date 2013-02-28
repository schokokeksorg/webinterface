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

  title("MySQL-Datenbanken");
  output('<p>Hier können Sie den Zugriff auf Ihre MySQL-Datenbanken verwalten. Die Einstellungen werden mit einer leichten Verzögerung (maximal 5 Minuten) in das System übertragen. Bitte beachten Sie, dass neue Zugänge also nicht umgehend funktionieren.</p>
  <p><strong>Hinweis:</strong> In dieser Matrix sehen Sie links die Datenbanken und oben die Benutzer, die Sie eingerichtet haben. In der Übersicht ist dargestellt, welcher Benutzer auf welche Datenbank Zugriff erhält. Klicken Sie auf die Symbole um die Zugriffsrechte zu ändern.</p>');

  $form = '
  <table>
  <tr><th>&#160;</th><th style="background-color: #729bb3; color: #fff;padding: 0.2em;" colspan="'.(count($users)+1).'">Benutzerkonten</th></tr>
  <tr><th style="background-color: #729bb3; color: #fff;padding: 0.2em; text-align: left;">Datenbanken</th>';

  foreach ($users as $user)
  {
    $username = $user["username"];
    //$username = str_replace('_', '_ ', $user['username']);
    $desc = '';
    if ($user['description']) {
      $desc = '<br /><span style="font-weight: normal; font-size: 80%; font-style: italic;">'.$user['description'].'</span>';
    } 
    $form .= "<th><span title=\"Erstellt: {$user['created']}\">{$username}</span>".$desc;
    $form .= "<br />".internal_link('description', other_icon("comment.png", 'Beschreibung ändern'), "username={$username}")."&#160;";
    $form .= internal_link("save", icon_delete("Benutzer »{$user['username']}« löschen"), "action=delete_user&user={$user['username']}")."</th>";
  }

  $servers = servers_for_databases();

  $formtoken = generate_form_token('mysql_permchange');

  foreach($dbs as $db)
  {
    $phpmyadmin = "https://mysql.{$servers[$db['name']]}/";
    $desc = '';
    if ($db['description']) {
      $desc = '<br /><span style="font-weight: normal; font-size: 80%; font-style: italic;">'.$db['description'].'</span>';
    } 
    $form .= "<tr><td style=\"border: 0px; font-weight: bold; text-align: right;\"><span title=\"Erstellt: {$db['created']}\">{$db['name']}</span>".$desc."<br />";
    $form .= internal_link('description', other_icon("comment.png", 'Datenbank-Beschreibung ändern'), "db={$db['name']}")."&#160;";
    $form .= internal_link("save", icon_delete("Datenbank »{$db['name']}« löschen"), "action=delete_db&db={$db['name']}")."&#160;";
    $form .= "<a href=\"".$phpmyadmin."\">".other_icon("database_go.png", "Datenbank-Verwaltung über phpMyAdmin")."</a>";
    $form .= "</td>";
    foreach ($users as $user) {
      $form .= '<td style="text-align: center;">';
      if (get_mysql_access($db['name'], $user['username'])) {
        $form .= internal_link('save', icon_enabled('Zugriff erlaubt; Anklicken zum Ändern'), "action=permchange&user={$user['username']}&db={$db['name']}&access=0&formtoken={$formtoken}");
      } else {
        $form .= internal_link('save', icon_disabled('Zugriff verweigern; Anklicken zum Ändern'), "action=permchange&user={$user['username']}&db={$db['name']}&access=1&formtoken={$formtoken}");
      }
      
    }
    $form .= "</tr>\n";
  }

  $form .= '
  </table>';

  
  output(html_form('mysql_databases', 'databases', '', $form));

  addnew('newdb', 'Neue Datenbank');
  addnew('newuser', 'Neuer DB-Benutzer');


  $myservers = array();
  foreach ($servers as $s) {
    if (! in_array($s, $myservers)) {
      $myservers[] = $s;
    }
  }

  output("<h4>Verwaltung der Datenbanken (phpMyAdmin)</h4>
  <p><img src=\"{$prefix}images/phpmyadmin.png\" style=\"width: 120px; height: 70px; float: right;\" />Zur Verwaltung der Datenbank-Inhalte stellen wir Ihnen eine stets aktualisierte Version von phpMyAdmin zur Verfügung.</p>");
  if (count($myservers) == 1) {
    output("<p><strong><a href=\"https://mysql.{$myservers[0]}/\">phpMyAdmin aufrufen</a></strong></p>");
  }
  else {
    output("<p><em>Ihre Datenbanken befinden sich auf unterschiedlichen Servern, daher müssen Sie die jeweils passende Adresse für phpMyAdmin benutzen. Klicken Sie auf das Symbol ".other_icon("database_go.png", "Datenbank-Verwaltung über phpMyAdmin")." oben neben der jeweiligen Datenbank.</em></p>");
  }


  $users = get_mysql_accounts($_SESSION['userinfo']['uid']);



  $my_users = array();
  foreach ($users as $u)
  {
    $my_users[$u['username']] = $u['username'];
  }
  $form = '<div>
  <label for="mysql_username">Benutzername:</label>&#160;'.html_select('mysql_username', $my_users).'
  &#160;&#160;&#160;
  <label for="password">Passwort:</label>&#160;<input type="password" name="mysql_password" id="password" />
  &#160;&#160;<input type="submit" value="Setzen" />
</div>';


  output('<h4>Passwort ändern</h4>
  <p>Hier können Sie das Passwort eines MySQL-Benutzeraccounts ändern bzw. neu setzen</p>

  '.html_form('mysql_databases', 'save', 'action=change_pw', $form).'<br />');



?>

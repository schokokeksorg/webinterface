<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('session/start.php');
require_once('inc/icons.php');
require_role([ROLE_SYSTEMUSER]);

global $prefix;

require_once('mysql.php');

$dbs = get_mysql_databases($_SESSION['userinfo']['uid']);
$users = get_mysql_accounts($_SESSION['userinfo']['uid']);
$servers = servers_for_databases();

title("MySQL-Datenbanken");
output('<p>Hier können Sie den Zugriff auf Ihre MySQL-Datenbanken verwalten. Die Einstellungen werden mit einer leichten Verzögerung (maximal 5 Minuten) in das System übertragen. Bitte beachten Sie, dass neue Zugänge also nicht umgehend funktionieren.</p>');

html_header('
<script>

  function makePasswd() {
    const pwchars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    const limit = 256 - (256 % pwchars.length);

    let passwd = "";
    let randval;
    for (let i = 0; i < 15; i++) {
      do {
        randval = window.crypto.getRandomValues(new Uint8Array(1))[0];
      } while (randval >= limit);
      passwd += pwchars[randval % pwchars.length];
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

if (count($dbs) > 0 || count($users) > 0) {
    output('<p><strong>Hinweis:</strong> In dieser Matrix sehen Sie links die Datenbanken und oben die Benutzer, die Sie eingerichtet haben. In der Übersicht ist dargestellt, welcher Benutzer auf welche Datenbank Zugriff erhält. Klicken Sie auf die Symbole um die Zugriffsrechte zu ändern.</p>');

    output('
  <table>
  <tr><th>&#160;</th><th style="background-color: #729bb3; color: #fff;padding: 0.2em;" colspan="' . (count($users) + 1) . '">Benutzerkonten</th></tr>
  <tr><th style="background-color: #729bb3; color: #fff;padding: 0.2em; text-align: left;">Datenbanken</th>');

    foreach ($users as $user) {
        $username = $user["username"];
        //$username = str_replace('_', '_ ', $user['username']);
        $desc = '';
        if ($user['description']) {
            $desc = '<br /><span style="font-weight: normal; font-size: 80%; font-style: italic;">' . filter_output_html($user['description']) . '</span>';
        }
        output("<th><span title=\"Erstellt: {$user['created']}\">{$username}</span>" . $desc);
        output("<br />" . internal_link('description', other_icon("comment.png", 'Beschreibung ändern'), "username={$username}") . "&#160;");
        output(internal_link("save", icon_delete("Benutzer »{$user['username']}« löschen"), "action=delete_user&user={$user['username']}") . "</th>");
    }


    $formtoken = generate_form_token('mysql_permchange');

    foreach ($dbs as $db) {
        $phpmyadmin = "https://mysql-{$servers[$db['name']]}/";
        $desc = '';
        if ($db['description']) {
            $desc = '<br /><span style="font-weight: normal; font-size: 80%; font-style: italic;">' . filter_output_html($db['description']) . '</span>';
        }
        output("<tr><td style=\"border: 0px; font-weight: bold; text-align: right;\"><span title=\"Erstellt: {$db['created']}\">{$db['name']}</span>" . $desc . "<br />");
        output(internal_link('description', other_icon("comment.png", 'Datenbank-Beschreibung ändern'), "db={$db['name']}") . "&#160;");
        output(internal_link("save", icon_delete("Datenbank »{$db['name']}« löschen"), "action=delete_db&db={$db['name']}") . "&#160;");
        output("<a href=\"" . $phpmyadmin . "\">" . other_icon("database_go.png", "Datenbank-Verwaltung über phpMyAdmin") . "</a>");
        output("</td>");
        foreach ($users as $user) {
            output('<td style="text-align: center;">');
            if (get_mysql_access($db['name'], $user['username'])) {
                output(internal_link('save', icon_enabled('Zugriff erlaubt; Anklicken zum Ändern'), "action=permchange&user={$user['username']}&db={$db['name']}&access=0&formtoken={$formtoken}"));
            } else {
                output(internal_link('save', icon_disabled('Zugriff verweigern; Anklicken zum Ändern'), "action=permchange&user={$user['username']}&db={$db['name']}&access=1&formtoken={$formtoken}"));
            }
        }
        output("</tr>\n");
    }

    output('</table>');
} else {
    output('<p><em>Sie haben bisher keine Datenbanken erstellt.</em></p>');
}

addnew('newdb', 'Neue Datenbank');
addnew('newuser', 'Neuer DB-Benutzer');

if (count($dbs) > 0) {
    $myservers = [];
    foreach ($servers as $s) {
        if (!in_array($s, $myservers)) {
            $myservers[] = $s;
        }
    }

    output("<h4>Verwaltung der Datenbanken (phpMyAdmin)</h4>
  <p><img src=\"{$prefix}images/phpmyadmin.png\" style=\"width: 120px; height: 70px; float: right;\" alt=\"phpMyAdmin Logo\">Zur Verwaltung der Datenbank-Inhalte stellen wir Ihnen eine stets aktualisierte Version von phpMyAdmin zur Verfügung.</p>");
    if (count($myservers) == 1) {
        output("<p><strong><a href=\"https://mysql-{$myservers[0]}/\">phpMyAdmin aufrufen</a></strong></p>");
    } else {
        output("<p><em>Ihre Datenbanken befinden sich auf unterschiedlichen Servern, daher müssen Sie die jeweils passende Adresse für phpMyAdmin benutzen. Klicken Sie auf das Symbol " . other_icon("database_go.png", "Datenbank-Verwaltung über phpMyAdmin") . " oben neben der jeweiligen Datenbank.</em></p>");
    }
}
if (count($users) > 0) {
    $users = get_mysql_accounts($_SESSION['userinfo']['uid']);



    $my_users = [];
    foreach ($users as $u) {
        $my_users[$u['username']] = $u['username'];
    }
    $form = '<div>
  <p><label for="mysql_username">Benutzername:</label>&#160;' . html_select('mysql_username', $my_users) . '</p>
  <p><label for="newpass">Passwort:</label>&#160;<input onchange="document.getElementById(\'newpass_display\').parentNode.style.display=\'none\'" type="password" name="newpass" id="newpass" value="" autocomplete="new-password"> <button type="button" onclick="setRandomPassword()">Passwort erzeugen</button></p>
<p style="display: none;">Automatisch erzeugtes Passwort: <input id="newpass_display" type="text" readonly="readonly" /></p>
  <p><input type="submit" value="Setzen"></p>
</div>';


    output('<h4>Passwort ändern</h4>
  <p>Hier können Sie das Passwort eines MySQL-Benutzeraccounts ändern bzw. neu setzen</p>

  ' . html_form('mysql_databases', 'save', 'action=change_pw', $form) . '<br />');
}

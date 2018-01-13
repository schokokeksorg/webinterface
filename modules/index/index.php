<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2018 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/


//require_role(array(ROLE_CUSTOMER, ROLE_SYSTEMUSER));

/*if ($user['realname'] == '')
  input_error('Ihr Name ist nicht im System gespeichert (siehe Stammdaten)!');
if ($user['email'] == '')
  input_error('Im System ist keine alternative eMail-Adresse gespeichert (siehe Stammdaten)!');
*/

switch ($_SESSION['role'])
{
case ROLE_ANONYMOUS:
  login_screen('');
  break;
case ROLE_VMAIL_ACCOUNT:
  $role = "{$_SESSION['mailaccount']}, angemeldet als E-Mail-Account";
  break;
case ROLE_MAILACCOUNT:
  $role = "{$_SESSION['mailaccount']}, angemeldet als IMAP/POP3-Account";
  break;
case ROLE_SYSTEMUSER:
  $role = "{$_SESSION['userinfo']['name']}, angemeldet als Benutzer";
  break;
case ROLE_SYSTEMUSER | ROLE_SUBUSER:
case ROLE_SYSTEMUSER | ROLE_CUSTOMER | ROLE_SUBUSER:
  $role = "{$_SESSION['subuser']}, Unternutzer von {$_SESSION['userinfo']['username']}";
  break;
case ROLE_CUSTOMER:
  $role = "{$_SESSION['customerinfo']['name']}, angemeldet als Kunde";
  break;
case ROLE_CUSTOMER | ROLE_SYSTEMUSER:
  $role = "{$_SESSION['customerinfo']['name']}, angemeldet als Kunde und Benutzer";
  break;
case ROLE_SYSTEMUSER | ROLE_SYSADMIN:
  $role = "{$_SESSION['userinfo']['name']}, angemeldet als Benutzer und Administrator";
  break;
case ROLE_CUSTOMER | ROLE_SYSTEMUSER | ROLE_SYSADMIN:
  $role = "{$_SESSION['customerinfo']['name']}, angemeldet als Kunde, Benutzer und Administrator";
  break;
default:
  system_failure('Interner Fehler (»Unbekannte Rolle: '.$_SESSION['role'].'«)');
}


title('Übersicht');
headline('Administration');
output('<p>Herzlich willkommen, '.$role.".</p>\n");

output("<p>Auf der linken Seite sehen Sie ein Auswahlmenü mit den Funktionen, die Ihnen in diesem Webinterface zur Verfügung stehen.</p>
<p>Nachfolgend sehen Sie eine Auswahl typischer Aufgaben.</p>\n");

$modules = get_modules_info();

$my_shortcuts = array();
foreach ($modules as $modname => $info) {
  if (file_exists('modules/'.$modname.'/shortcuts.php')) {
    $shortcuts = array();
    include('modules/'.$modname.'/shortcuts.php');
    foreach ($shortcuts as $shortcut) {
      $shortcut['module'] = $modname;
      $my_shortcuts[$shortcut['weight'].$modname] = $shortcut;
    }
  }
}
krsort($my_shortcuts);
DEBUG($my_shortcuts);


output("<div class=\"overview\">");
foreach ($my_shortcuts as $shortcut) {
    $icon = "images/default.png";
    if (file_exists("images/".$shortcut['icon'])) {
      $icon = "images/".$shortcut['icon'];
    }
    $alert = '';
    if (isset($shortcut['alert']) && $shortcut['alert']) {
      $alert = '<br /><span style="color: red;">('.$shortcut['alert'].')</span>';
    }
    output("<div class=\"block\">".internal_link($prefix.'go/'.$shortcut['module'].'/'.$shortcut['file'], "<img src=\"{$prefix}{$icon}\" alt=\"\" /> {$shortcut['title']} {$alert}")."</div>");
  
}
output('</div>');

if (have_module('systemuser') && $_SESSION['role'] & ROLE_SYSTEMUSER) {
  ini_set('include_path', ini_get('include_path').':modules/systemuser/include');
  include('modules/systemuser/overview.php');
}


if (have_module('email') && $_SESSION['role'] & ROLE_VMAIL_ACCOUNT) {
  include('modules/email/vmailoverview.php');
  output("<div class=\"vmailoverview\">".$content."</div>");
}


?>

<?php

require_once('session/start.php');




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
      $my_shortcuts[$shortcut['weight']] = $shortcut;
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


if (have_module('email') && $_SESSION['role'] & ROLE_VMAIL_ACCOUNT) {
  include('modules/email/vmailoverview.php');
  output("<div class=\"vmailoverview\">".$content."</div>");
}


?>

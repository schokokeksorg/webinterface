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


output('<h3>Administration</h3>
<p>Herzlich willkommen, '.$role.".</p>\n");

output("<p>Auf der linken Seite sehen Sie ein Auswahlmenü mit den Funktionen, die Ihnen in diesem Webinterface zur Verfügung stehen.</p>
<p>Nachfolgend sehen Sie eine Auswahl typischer Aufgaben.</p>\n");

output("<div class=\"overview\">");

# Modul "email"
if (have_module('email') && ($_SESSION['role'] & ROLE_MAILACCOUNT || $_SESSION['role'] & ROLE_VMAIL_ACCOUNT)) {
  output("<div class=\"block\">".internal_link("../email/chpass", "<img src=\"{$prefix}images/pwchange.png\" alt=\"\" /> Passwort ändern ")."</div>");
}

# Modul "index", kann man nicht ausschalten
if ($_SESSION['role'] & ROLE_CUSTOMER || $_SESSION['role'] & ROLE_SYSTEMUSER) {
  output("<div class=\"block\">".internal_link("chpass", "<img src=\"{$prefix}images/pwchange.png\" alt=\"\" /> Passwort ändern ")."</div>");
}

# Modul "invoice"
if (have_module('invoice') && $_SESSION['role'] & ROLE_CUSTOMER) {
  output("<div class=\"block\">".internal_link("../invoice/current", "<img src=\"{$prefix}images/invoice.png\" alt=\"\" /> Ihre Rechnungen ")."</div>");
}

if ($_SESSION['role'] & ROLE_SYSTEMUSER) {
  if (have_module('email'))
    output("<div class=\"block\">".internal_link("../email/vmail", "<img src=\"{$prefix}images/email.png\" alt=\"\" /> E-Mail-Adressen verwalten ")."</div>");
  if (have_module('vhosts'))
    output("<div class=\"block\">".internal_link("../vhosts/vhosts", "<img src=\"{$prefix}images/webserver.png\" alt=\"\" /> Webserver-Einstellungen ")."</div>");
  if (have_module('mysql'))
    output("<div class=\"block\">".internal_link("../mysql/databases", "<img src=\"{$prefix}images/mysql.png\" alt=\"\" /> MySQL-Datenbanken ")."</div>");
}
 
if (have_module('jabber') && $_SESSION['role'] & ROLE_CUSTOMER) {
  output("<div class=\"block\">".internal_link("../jabber/accounts", "<img src=\"{$prefix}images/jabber.png\" alt=\"\" /> Jabber-Accounts ")."</div>");
}

output("</div>");

?>

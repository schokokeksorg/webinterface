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
case ROLE_CUSTOMER | ROLE_SYSTEMUSER | ROLE_SYSADMIN:
  $role = "{$_SESSION['customerinfo']['name']}, angemeldet als Kunde, Benutzer und Administrator";
  break;
default:
  system_failure('Interner Fehler (»Unbekannte Rolle: '.$_SESSION['role'].'«)');
}


output('<h3>Administration</h3>
<p>Herzlich willkommen, '.$role.'.</p>');


?>

<?php

$role = $_SESSION['role'];

require_once('include/hasdomain.php');
require_once('include/hasaccount.php');

if ($role & ROLE_SYSTEMUSER) {
  $menu["email_vmail"] = array("label" => "E-Mail", "file" => "vmail", "weight" => 3);
}
if ($role & ROLE_VMAIL_ACCOUNT)
{
  $menu['email_edit'] = array("label" => "Einstellungen", "file" => "edit", "weight" => 10);
}
if ($role & (ROLE_VMAIL_ACCOUNT | ROLE_MAILACCOUNT))
{
  $menu['email_chpass'] = array("label" => "Passwort ändern", "file" => "chpass", "weight" => 15);
}
if ($role & ROLE_SYSTEMUSER) {
  $menu["email_domains"] = array("label" => "Mail-Verwaltung", "file" => "domains", "weight" => 1, "submenu" => "domains_domains");
}
if ($role & ROLE_SYSTEMUSER && (user_has_accounts() || ! user_has_vmail_domain() || user_has_dotcourier_domain() ) )
{
  $menu["email_imap"] = array("label" => "IMAP/POP3", "file" => "imap", "weight" => 20, 'submenu' => "email_vmail");
}


?>

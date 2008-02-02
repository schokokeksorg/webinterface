<?php

require_once('include/hasaccount.php');

$role = $_SESSION['role'];

if ($role & ROLE_SYSTEMUSER && (user_has_accounts() || ! user_has_vmail_domain() || user_has_regular_domain() ) )
{
  $menu["imap_accounts"] = array("label" => "IMAP/POP3", "file" => "accounts.php", "weight" => 10);
}
elseif ($role & ROLE_MAILACCOUNT)
{
  $menu["imap_chpass"] = array("label" => "Passwort ändern", "file" => "chpass.php", "weight" => 10);
}


?>

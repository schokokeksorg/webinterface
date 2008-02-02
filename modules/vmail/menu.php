<?php

$role = $_SESSION['role'];

require_once('include/hasdomain.php');

if (($role & ROLE_SYSTEMUSER) && user_has_vmail_domain())
{
  $menu["vmail_accounts"] = array("label" => "E-Mail", "file" => "accounts.php", "weight" => 10);
}
if ($role & ROLE_VMAIL_ACCOUNT)
{
  $menu['vmail_chpass'] = array("label" => "Passwort ändern", "file" => "chpass.php", "weight" => 15);
}

?>

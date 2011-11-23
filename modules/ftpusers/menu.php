<?php

$role = $_SESSION['role'];

if ($role & ROLE_SYSTEMUSER)
{
  if (have_module('systemuser')) {
    $menu["ftpusers_accounts"] = array("label" => "FTP-Zugriff", "file" => "accounts", "weight" => 35, 'submenu' => 'systemuser_account');
  } else {
  $menu["ftpusers_accounts"] = array("label" => "FTP-Zugriff", "file" => "accounts", "weight" => 35);
  }
}

?>

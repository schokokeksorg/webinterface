<?php

$role = $_SESSION['role'];

if ($role & ROLE_SYSTEMUSER)
{
  $menu["ftpusers_accounts"] = array("label" => "FTP-Zugriff", "file" => "accounts", "weight" => 35);
}

?>

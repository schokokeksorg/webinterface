<?php

$role = $_SESSION['role'];

switch ($role)
{
  case ROLE_SYSTEMUSER:
    $menu["imap_accounts"] = array("label" => "IMAP/POP3", "file" => "accounts.php", "weight" => 10);
    
}

?>

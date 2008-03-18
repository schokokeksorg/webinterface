<?php

$role = $_SESSION['role'];

if ($role & ROLE_CUSTOMER)
{
  $menu["invoice_current"] = array("label" => "Rechnungen", "file" => "current.php", "weight" => 2);
  $menu["invoice_upcoming"] = array("label" => "zukünftige Rechnungen", "file" => "upcoming.php", "weight" => 2, "submenu" => "invoice_current");

    
}

?>

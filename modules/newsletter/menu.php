<?php

$role = $_SESSION['role'];

if ($role & ROLE_CUSTOMER)
{
  $menu["newsletter_newsletter"] = array("label" => "Newsletter", "file" => "newsletter", "weight" => 5, "submenu" => "index_index");

    
}

?>

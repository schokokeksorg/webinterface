<?php

$role = $_SESSION['role'];

if ($role & ROLE_SYSTEMUSER)
{
  $menu['mailman_lists'] = array("label" => "Mailinglisten", "file" => "lists", "weight" => 5, 'submenu' => 'email_vmail');
}

?>

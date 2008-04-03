<?php

$role = $_SESSION['role'];

if ($role & ROLE_SYSTEMUSER)
{
  $menu['greylisting_whitelist'] = array("label" => "Greylisting", "file" => "whitelist.php", "weight" => 5, 'submenu' => 'email_vmail');
}

?>

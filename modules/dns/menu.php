<?php

$role = $_SESSION['role'];


if ($role & ROLE_SYSTEMUSER)
{
  $menu["dns_dns"] = array("label" => "DNS-Einträge", "file" => "dns", "weight" => 10, "submenu" => "domains_domains");
  $menu["dns_dyndns"] = array("label" => "DynDNS", "file" => "dyndns", "weight" => 11, "submenu" => "domains_domains");
}

?>

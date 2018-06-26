<?php

$role = $_SESSION['role'];
if ($role & ROLE_SYSADMIN) {
    $menu["buchhaltung_report"] = array("label" => "Report", "file" => "report", "weight" => -10);
}

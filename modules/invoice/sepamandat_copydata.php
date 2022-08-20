<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/base.php');
require_once('inc/debug.php');

require_once('invoice.php');

$kundenname = $_SESSION['customerinfo']['name'];
$id = (int) $_SESSION['customerinfo']['customerno'];
$result = db_query("SELECT CONCAT(adresse, '\\\\n', plz, ' ', ort) AS adresse FROM kundendaten.kunden WHERE id=?", [$id]);
$r = $result->fetch();

header("Content-Type: text/javascript");
echo ' { "kundenname": "'.$kundenname.'", "adresse": "'.$r["adresse"].'" } ';
die();

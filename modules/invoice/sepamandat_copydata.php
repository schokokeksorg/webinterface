<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2013 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/base.php');
require_once('inc/debug.php');

require_once('invoice.php');

$kundenname = $_SESSION['customerinfo']['name'];
$id = (int) $_SESSION['customerinfo']['customerno'];
$result = db_query("SELECT CONCAT(adresse, '\\\\n', plz, ' ', ort) AS adresse FROM kundendaten.kunden WHERE id={$id}");
$r = mysql_fetch_assoc($result);

header("Content-Type: text/javascript");
echo ' { "kundenname": "'.$kundenname.'", "adresse": "'.$r["adresse"].'" } ';
die();



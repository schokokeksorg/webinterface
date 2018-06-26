<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2018 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see
https://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/base.php');
require_once('inc/icons.php');

require_once('useraccounts.php');

require_role(ROLE_SYSTEMUSER);

/* diese Datei wird auf der startseite eingebunden */

$acc = get_account_details($_SESSION['userinfo']['uid'], $_SESSION['userinfo']['customerno']);
$usedquota = get_used_quota($acc['uid']);
$quota = array();
$multiserver = count($usedquota) > 1;
$need_more_storage = false;
foreach ($usedquota as $q) {
    $mailbar = '';
    $mailstring = '';
    $mailpercent = round(($q['mailquota'] / $q["systemquota"]) * 100);
    $mailwidth = 2 * min($mailpercent, 100);

    if ($q["mailquota"] > 0) {
        $mailstring = "<br />(davon {$q["mailquota"]} MB für Postfächer reserviert)";
        $mailbar = "<div style=\"font-size: 1px; background-color: blue; height: 10px; width: {$mailwidth}px; margin: 0; padding: 0; float: left;\">&#160;</div>";
    }

    $percent = round((($q["systemquota_used"]+$q["mailquota"]) / $q["systemquota"]) * 100);
    if ($percent > 90) {
        $need_more_storage = true;
    }
    $color = ($percent > 99 ? 'red' : ($percent > 80 ? "yellow" : "green"));
    $width = 2 * min($percent, 100) - $mailwidth;

    $used_space = $q['systemquota_used'] + $q['mailquota'];
    $msg = "";
    if ($multiserver) {
        $msg = "<p>Server <strong>{$q['server']}</strong><br />";
    }
    $quota[] = $msg."{$percent}%: {$used_space} MB von {$q['systemquota']} MB belegt{$mailstring}.</p> 
        <div style=\"margin: 0; padding: 0; width: 200px; border: 1px solid black;\">{$mailbar}<div style=\"font-size: 1px; background-color: {$color}; height: 10px; width: {$width}px; margin: 0; margin-left: {$mailwidth}px; padding: 0;\">&#160;</div></div>";
}
$customer = get_customer_info($_SESSION['userinfo']['customerno']);
$quotastring = implode('', $quota);
output("<h5>Stammdaten</h5>
<div style=\"margin-left: 2em;\">
<p>Benutzername: <strong>{$acc['username']}</strong></p>
<p>Servername".($multiserver ? " (primär)" : '').": <strong>".get_server_by_id($acc['server'])."</strong></p>
<p>Tipp: <a href=\"https://wiki.schokokeks.org/Dateizugriff\">Wiki-Anleitung zum Dateizugriff bzw. zum Ändern der Dateien Ihrer Website.</a></p>
");
output("</div>\n");
output("<h5>Speicherplatz</h5><div style=\"margin-left: 2em;\">{$quotastring}</div>");
if (have_module('invoice') && $need_more_storage) {
    addnew('../invoice/more_storage?section='.$section, 'Mehr Speicherplatz bestellen');
}

output("<p>Die Werte für den verbrauchten Speicherplatz werden periodisch eingelesen und hier verzögert angezeigt!</p>");

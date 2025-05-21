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
require_once('inc/icons.php');

require_once('useraccounts.php');

require_role(ROLE_CUSTOMER);

title("System-Benutzeraccounts");


if (!customer_may_have_useraccounts()) {
    warning("Sie haben bisher keine Benutzeraccounts. Der erste (»Stamm-«)Account muss von einem Administrator angelegt werden.");
} else {
    $accounts = list_useraccounts();
    $shells = available_shells();

    output("<p>Folgende Benutzeraccounts haben Sie bisher:</p>");
    output("<table><tr><th>Benutzeraccount</th><th>Speicherplatz<sup>*</sup></th><th>Aktionen</th></tr>");
    foreach ($accounts as $acc) {
        $shell = $shells[$acc['shell']];
        $usedquota = get_used_quota($acc['uid']);
        $quota = [];
        foreach ($usedquota as $q) {
            $mailbar = '';
            $mailstring = '';
            $mailpercent = round(($q['mailquota'] / max($q["systemquota"], 1)) * 100);
            $mailwidth = 2 * min($mailpercent, 100);

            if ($q["mailquota"] > 0) {
                $mailstring = "<br />(davon {$q["mailquota"]} MB für Postfächer reserviert)";
                $mailbar = "<div style=\"font-size: 1px; background-color: blue; height: 10px; width: {$mailwidth}px; margin: 0; padding: 0; float: left;\">&#160;</div>";
            }

            $percent = round((($q["systemquota_used"] + $q["mailquota"]) / max($q["systemquota"], 1)) * 100);
            $color = ($percent > 99 ? 'red' : ($percent > 80 ? "yellow" : "green"));
            $width = 2 * min($percent, 100) - $mailwidth;

            $used_space = $q['systemquota_used'] + $q['mailquota'];
            $quota[] = "<p>Server <strong translate=\"no\">{$q['server']}</strong><br />{$percent}%: {$used_space} MB von {$q['systemquota']} MB belegt{$mailstring}.</p> 
        <div style=\"margin: 0; padding: 0; width: 200px; border: 1px solid black;\">{$mailbar}<div style=\"font-size: 1px; background-color: {$color}; height: 10px; width: {$width}px; margin: 0; margin-left: {$mailwidth}px; padding: 0;\">&#160;</div></div>";
        }
        $realname = $acc['name'] ? $acc['name'] : $_SESSION['customerinfo']['name'];
        $quotastring = implode('', $quota);
        output("<tr><td><p><strong translate=\"no\">{$acc['username']}</strong> - <span translate=\"no\">" . filter_output_html($realname) . "</span></p><p style=\"color: #555;\">Existiert seit {$acc['erstellungsdatum']}<br />Verwendete Shell: {$shell}</p></td>");
        output("<td>{$quotastring}</td>");
        output("<td>" . internal_link('edit', other_icon('user_edit.png', 'Bearbeiten'), "uid={$acc['uid']}"));

        if (!customer_useraccount($acc['uid'])) {
            output(" &#160; " . internal_link('pwchange', icon_pwchange('Passwort neu setzen'), "uid={$acc['uid']}"));
            #output(" &#160; ".internal_link('deluser', other_icon('user_delete.png', 'Benutzer löschen'), "uid={$acc['uid']}"));
        }
        output("</td></tr>\n");
    }
    output("</table><p><sup>*</sup>) Die Werte für den verbrauchten Speicherplatz werden periodisch eingelesen und hier erst verspätet angezeigt!</p>");
    $customerquota = get_customer_quota();
    $freequota = $customerquota['max'] - $customerquota['assigned'];
    if ($freequota > 10) { // Gewisse Unschärfe
        $percent = round(($customerquota['assigned'] / $customerquota['max']) * 100);
        $width = 5 * min($percent, 100);
        $color = ($percent > 99 ? 'red' : ($percent > 80 ? "yellow" : "green"));
        $maxstr = ($customerquota['max'] > 1024) ? number_format($customerquota['max'] / 1024, 1, ',', '.') . ' GB' : $customerquota['max'] . ' MB';
        $assignedstr = ($customerquota['assigned'] > 1024) ? number_format($customerquota['assigned'] / 1024, 1, ',', '.') . ' GB' : $customerquota['assigned'] . ' MB';
        $freestr = ($freequota > 1024) ? number_format($freequota / 1024, 1, ',', '.') . ' GB' : $freequota . ' MB';
        output('<p>Ihrem Kundenaccount stehen insgesamt ' . $maxstr . ' zur Verfügung, davon sind ' . $assignedstr . ' den Benutzerkonten zugewiesen und noch ' . $freestr . ' frei verfügbar.</p>');
        output("<div style=\"margin: 0; padding: 0; width: 500px; border: 1px solid black;\"><div style=\"font-size: 1px; background-color: {$color}; height: 10px; width: {$width}px; margin: 0; margin-left: 0; padding: 0;\">&#160;</div></div>");
        output('<p class="warning"><b>Hinweis:</b><br/>Ihnen steht mehr Speicherplatz zur Verfügung als Ihren Benutzeraccounts zugewiesen ist. Sie können den Speicherplatz der einzelnen Benutzerkonten noch erhöhen.</p>');
    } else {
        output('<p>Der für Sie reservierte Speicherplatz ist vollständig auf Ihre Benutzeraccounts verteilt.</p>');
        if (have_module('invoice')) {
            addnew('../invoice/more_storage?section=systemuser_account', 'Mehr Speicherplatz bestellen');
        }
    }
}

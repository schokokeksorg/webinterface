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
require_once('inc/security.php');
require_role(ROLE_VMAIL_ACCOUNT);

require_once('include/vmail.php');

$id = get_vmail_id_by_emailaddr($_SESSION['mailaccount']);
$acc = get_account_details($id, false);
$actions = [];
DEBUG($acc);

$content = '<h3>Aktueller Speicherplatzverbrauch</h3>';

$percent = round(($acc["quota_used"] / $acc["quota"]) * 100);
$color = ($percent > 95 ? 'red' : ($percent > 75 ? "yellow" : "green"));
$width = 2 * min($percent, 100);
$content .= "<div><div style=\"margin: 2px 0; padding: 0; width: 200px; border: 1px solid black;\"><div style=\"font-size: 1px; background-color: {$color}; height: 10px; width: {$width}px; margin: 0; padding: 0;\">&#160;</div></div> {$acc['quota_used']} MB von {$acc['quota']} MB belegt</div>";

$content .= '<h3>Einstellungen</h3>
<p>Eingehende E-Mails für Ihre Adresse werden wie folgt verarbeitet:</p>';

$content .= '<p>' . other_icon('go.png') . " Ablegen in Ihrer Mailbox</p>";


if ($acc['autoresponder']) {
    $now = date('Y-m-d H:i:s');
    $valid_from = $acc['autoresponder']['valid_from'];
    $valid_from_string = '';
    if ($valid_from) {
        $valid_from_string = date('d.m.Y', strtotime($valid_from));
    }
    $valid_until = $acc['autoresponder']['valid_until'];
    $valid_until_string = '';
    if ($valid_until) {
        $valid_until_string = date('d.m.Y', strtotime($valid_until));
    }
    if ($valid_from == null) {
        // Autoresponder abgeschaltet
        //$content .= '<p>'.other_icon('go.png')." Es wird keine automatische Antwort versendet</p>";
    } elseif ($valid_from > $now) {
        $content .= '<p>' . other_icon('go.png') . " Es wird ab dem {$valid_from_string} eine automatische Antwort versendet</p>";
    } elseif ($valid_until == null) {
        $content .= '<p>' . other_icon('go.png') . " Es wird eine automatische Antwort versendet</p>";
    } elseif ($valid_until > $now) {
        $content .= '<p>' . other_icon('go.png') . " Es wird eine automatische Antwort versendet, jedoch nicht mehr ab dem {$valid_until_string}</p>";
    } elseif ($valid_until < $now) {
        $content .= '<p>' . other_icon('go.png') . " Es wird seit dem {$valid_until_string} keine automatische Antwort mehr versendet</p>";
    }
}

foreach ($acc['forwards'] as $fwd) {
    $content .= '<p>' . other_icon('go.png') . " Weiterleitung an <strong>" . filter_output_html($fwd['destination']) . "</strong></p>";
}


$content .= '<h3>Nachrichten-Abruf</h3>
<p>' . internal_link('/go/email/logindata', 'Zugangsdaten für E-Mail-Abruf anzeigen', 'server=' . get_server_by_id($acc['server']) . '&type=vmail') . '</p>';

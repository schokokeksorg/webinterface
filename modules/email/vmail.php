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
require_role(ROLE_SYSTEMUSER);
require_once('inc/javascript.php');
javascript();

require_once('hasdomain.php');

if (!user_has_vmail_domain()) {
    title("E-Mail-Verwaltung");

    output('
<p>Sie können bei '.config('company_name').' die E-Mails Ihrer Domains auf zwei unterschiedliche Arten empfangen.</p>
<ol><li>Sie können einfache E-Mail-Konten erstellen, die ankommende E-Mails speichern oder weiterleiten.</li>
<li>Sie können die manuelle Verwaltung wählen, bei der Sie passende .courier-Dateien für den Empfang und
manuelle POP3/IMAP-Konten für den Abruf erstellen können.</li></ol>
<p>Diese Wahlmöglichkeit haben Sie pro Domain bzw. Subdomain. eine Mischung beider Verfahren ist nicht möglich. 
Subdomains können grundsätzlich nur durch Administratoren eingerichtet und verändert werden.</p>
<p>Sie haben bisher keine Domains, die auf Web-basierte Verwaltung von E-Mail-Adressen eingerichtet sind.</p>

<p> </p>

<p>Besuchen Sie die '.internal_link('domains', 'Domain-Einstellungen').' um diese Auswahl für Ihre Domains zu ändern.</p>

<p>Wenn Sie die manuelle Einrichtung möchten oder keine eigene Domain nutzen, können Sie unter '.internal_link('imap', 'POP3/IMAP').' manuelle POP3-/IMAP-Konten erstellen.</p>

');
} else {
    $filter = null;
    if (isset($_REQUEST['filter']) && $_REQUEST['filter'] != '') {
        $filter = $_REQUEST['filter'];
    }

    require_once('vmail.php');

    $domains = get_vmail_domains();
    $all_accounts = get_vmail_accounts();

    $sorted_by_domains = [];
    foreach ($all_accounts as $account) {
        if (array_key_exists($account['domain'], $sorted_by_domains)) {
            array_push($sorted_by_domains[$account['domain']], $account);
        } else {
            $sorted_by_domains[$account['domain']] = [$account];
        }
    }

    DEBUG($sorted_by_domains);

    title('E-Mail-Accounts');

    addnew("edit", "Neue E-Mail-Adresse anlegen");

    if (count($domains) > 0) {
        // Filter-Funktion
        if (count($all_accounts) > 10 || $filter) {
            $form = '<p><label for="filter">Filter für die Anzeige:</label> <input type="text" name="filter" id="filter" value="'.$filter.'"><button type="button" id="clear" title="Filter leeren">&times;</button><input type="submit" value="Filtern!"></p>';
            output(html_form('vmail_filter', 'vmail', '', $form));
        }

        output('
            <p>Folgende E-Mail-Konten sind aktuell eingerichtet:</p>
            ');
        foreach ($domains as $dom) {
            if ($filter && strpos($dom['domainname'], $filter) === false) {
                // Die Domain entspricht nicht dem Filter, schau die Postfächer an
                $account_found = false;
                if (array_key_exists($dom['id'], $sorted_by_domains)) {
                    $accounts_on_domain = $sorted_by_domains[$dom['id']];
                    foreach ($accounts_on_domain as $this_account) {
                        if (strpos($this_account['local'], $filter) !== false) {
                            $account_found = true;
                        }
                    }
                }
                if (!$account_found) {
                    continue;
                }
            }
            output('
                <h4>'.$dom['domainname'].' <small>('.other_icon('information.png', 'Zugangsdaten anzeigen').' '.internal_link('logindata', 'Zugangsdaten für E-Mail-Abruf anzeigen', 'server='.get_server_by_id($dom['server']).'&type=vmail').')</small></h4>
                <div style="margin-left: 2em; margin-top: 0.5em; padding: 0.1em 0.5em;">');
            if (array_key_exists($dom['id'], $sorted_by_domains)) {
                $accounts_on_domain = $sorted_by_domains[$dom['id']];

                foreach ($accounts_on_domain as $this_account) {
                    if ($filter &&
                    (strpos($dom['domainname'], $filter) === false &&
                     strpos($this_account['local'], $filter) === false)) {
                        continue;
                    }
                    $acc = get_account_details($this_account['id']);
                    $actions = [];
                    DEBUG($acc);
                    if ($acc['password'] != '') {
                        $percent = round(($acc["quota_used"] / $acc["quota"]) * 100);
                        $color = ($percent > 95 ? 'red' : ($percent > 75 ? "yellow" : "green"));
                        $width = 2 * min($percent, 100);
                        $quotachart = "<div style=\"margin: 2px 0; padding: 0; width: 200px; border: 1px solid black;\"><div style=\"font-size: 1px; background-color: {$color}; height: 10px; width: {$width}px; margin: 0; padding: 0;\">&#160;</div></div> {$acc['quota_used']} MB von {$acc['quota']} MB belegt";
                        array_push($actions, "Ablegen in Mailbox<br />".$quotachart);
                    }
                    if ($acc['autoresponder']) {
                        $now = date('Y-m-d');
                        $valid_from = $acc['autoresponder']['valid_from'];
                        $valid_until = $acc['autoresponder']['valid_until'];
                        if ($valid_from == null) {
                            // Autoresponder abgeschaltet
                            //array_push($actions, "<strike>Automatische Antwort versenden</strike> (Abgeschaltet)");
                        } elseif ($valid_from > $now) {
                            $valid_from_string = date('d.m.Y', strtotime($acc['autoresponder']['valid_from']));
                            array_push($actions, "<strike>Automatische Antwort versenden</strike> (Wird aktiviert am {$valid_from_string})");
                        } elseif ($valid_until == null) {
                            array_push($actions, "Automatische Antwort versenden (Unbefristet)");
                        } elseif ($valid_until > $now) {
                            $valid_until_string = date('d.m.Y', strtotime($acc['autoresponder']['valid_until']));
                            array_push($actions, "Automatische Antwort versenden (Wird deaktiviert am {$valid_until_string})");
                        } elseif ($valid_until < $now) {
                            $valid_until_string = date('d.m.Y', strtotime($acc['autoresponder']['valid_until']));
                            array_push($actions, "<strike>Automatische Antwort versenden</strike> (Automatisch abgeschaltet seit {$valid_until_string})");
                        }
                    }
                    foreach ($acc['forwards'] as $fwd) {
                        array_push($actions, "Weiterleitung an <strong>".filter_output_html($fwd['destination'])."</strong>");
                    }
                    $dest = '';
                    if (count($actions) > 0) {
                        $dest = "<ul>";
                        foreach ($actions as $a) {
                            $dest .= "<li>{$a}</li>";
                        }
                        $dest .= '</ul>';
                    }
                    if ($acc['smtpreply']) {
                        output('<p><strike>'.filter_output_html($acc['local'].'@'.$this_account['domainname']).'</strike> '.internal_link("save", '<img src="'.$prefix.'images/delete.png" alt="löschen" title="Dieses Konto löschen"/>', "action=delete&id=".$acc['id']).'</p>');
                        output("<ul><li>".icon_disabled()." Diese Adresse ist stillgelegt. <strong>".internal_link('suspend', 'Stilllegung ändern/aufheben', 'account='.$acc['id']).'</strong></li></ul>');
                    } else {
                        output('<p>'.internal_link('edit', filter_output_html($acc['local'].'@'.$this_account['domainname']), 'id='.$acc['id']).' '.internal_link("save", '<img src="'.$prefix.'images/delete.png" alt="löschen" title="Dieses Konto löschen"/>', "action=delete&id=".$acc['id']).'</p>');
                        output('<p>'.$dest.'</p>');
                    }
                }
            } else {
                output('<p><em>Bisher keine E-Mail-Adressen unter dieser Domain.</em></p>');
            }
            addnew("edit", "Neue E-Mail-Adresse anlegen", "domain={$dom['id']}");
            output('</div>');
        }
    } else {
        output('<p><em>Es sind bisher keine Ihrer Domains für Mail-Empfang eingerichtet.</em></p>');
    }


    /* FIXME: Das sollte nur kommen, wenn der IMAP/POP3-Menü-Eintrag nicht da ist */
    output('<p style="font-size: 90%;padding-top: 0.5em; border-top: 1px solid black;">Hinweis: '.filter_output_html(config('company_name')).' bietet für fortgeschrittene Nutzer die manuelle Einrichtung von POP3/IMAP-Accounts.<br/>'.internal_link("imap", "Neuen POP3/IMAP-Account anlegen", "action=create").'</p>');
}

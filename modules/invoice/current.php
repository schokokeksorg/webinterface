<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('session/start.php');
require_once('inc/icons.php');

require_once('invoice.php');

require_role(ROLE_CUSTOMER);

title('Rechnungen');

$show_paid = (isset($_GET['paid']) && $_GET['paid'] == '1');

$invoices = my_invoices();

$first = true;
$invoices_to_show = [];
foreach ($invoices as $i) {
    if ($first || $show_paid || $i['bezahlt'] == 0) {
        $first = false;
        array_push($invoices_to_show, $i);
    }
}

if (count($invoices_to_show) == 0) {
    $error = 'Keine aktuelle Rechnung gefunden.';
    if (count($invoices) == 0) {
        $error = 'Bisher keine Rechnungen vorhanden.';
    }
    if ($show_paid) {
    }

    output('<p><em>'.$error.'</em></p>');
} else {
    if ($show_paid) {
        output('<p>Hier können Sie Ihre bisherigen Rechnungen einsehen und herunterladen.</p>');
    } else {
        output('<p>Hier sehen Sie Ihre neueste Rechnung. Ältere, bereits bezahlte Rechnungen können sie über den untenstehenden Link einblenden.</p>');
    }
    output('<table class="nogrid"><tr><th>Nr.</th><th>Datum</th><th>Gesamtbetrag</th><th>bezahlt?</th><th>Herunterladen</th></tr>');

    $odd = true;
    foreach ($invoices_to_show as $invoice) {
        $bezahlt = 'Nein';
        $class = 'unpaid';
        if ($invoice['bezahlt'] == 1) {
            $bezahlt = 'Ja';
            $class = 'paid';
        } else {
            $l = get_lastschrift($invoice['id']);
            if ($l) {
                $bezahlt = 'Wird abgebucht<br/>am '.$l['buchungsdatum'];
                $class = 'paid';
                if ($l['status'] == 'rejected') {
                    $bezahlt  = 'Abbuchung zurückgewiesen';
                    $class = 'unpaid';
                }
            }
        }
        $odd = !$odd;
        $class .= ($odd ? " odd" : " even");
        output("<tr class=\"{$class}\"><td class=\"number\">".internal_link("html", $invoice['id'], "id={$invoice['id']}")."</td><td>{$invoice['datum']}</td><td class=\"number\">{$invoice['betrag']} €</td><td>{$bezahlt}</td><td>".internal_link("pdf", "<img src=\"{$prefix}images/pdf.png\" width=\"22\" height=\"22\" alt=\"PDF\"/>", "id={$invoice['id']}")."</td></tr>\n");
    }

    output('</table>');
}

if (!$show_paid) {
    $number = count($invoices) - count($invoices_to_show);
    if ($number > 0) {
        output('<p>'.internal_link('', other_icon('control_fastforward.png')." Zeige $number ältere Rechnungen", 'paid=1').'</p>');
    }
}


output('<h3>Bezahlung per Lastschrift</h3>');

output('<p>Gerne buchen wir Ihre Beiträge von Ihrem Konto ab. Bei Lastschriftzahlung werden Sie durch Zustellung einer Rechnung informiert, die Abbuchung erfolgt dann eine Woche später.</p>');

$mandate = get_sepamandate();
if ($mandate) {
    output('<p>Folgende Mandate sind bisher erteilt worden (momentan gültiges Mandat ist Fett dargestellt):</p>
<table>
<tr><th>Mandatsreferenz</th><th>IBAN</th><th>Gültigkeit</th></tr
');
    foreach ($mandate as $m) {
        $gueltig = 'ab '.$m['gueltig_ab'];
        if ($m['gueltig_ab'] < date('Y-m-d')) {
            $gueltig = 'seit '.$m['gueltig_ab'];
        }
        if ($m['gueltig_bis']) {
            $gueltig = $m['gueltig_ab'].' - '.$m['gueltig_bis'];
        }
        $aktiv = false;
        if ($m['gueltig_ab'] <= date('Y-m-d') && ($m['gueltig_bis'] == null || $m['gueltig_bis'] >= date('Y-m-d'))) {
            $aktiv = true;
        }
        output('<tr><td'.($aktiv ? ' style="font-weight: bold;"' : '').'>'.internal_link('sepamandat_detail', $m['mandatsreferenz'], 'ref='.$m['mandatsreferenz']).'</td><td>'.$m['iban'].'</td><td>'.$gueltig.'</td></tr>');
    }
    output('</table>');
}


if ($mandate) {
    addnew('sepamandat', 'Hat sich Ihre Bankverbindung geändert? Erteilen Sie uns ein neues Lastschrift-Mandat');
} else {
    addnew('sepamandat', 'Erteilen Sie uns ein Lastschrift-Mandat');
}

output('<p>Sie können Ihr Mandat jederzeit widerrufen. Senden Sie uns dazu bitte eine entsprechende E-Mail.</p>');



output("<h3>Rechnungs-Vorschau</h3>");
output('<p>Hier sehen Sie einen Überblick über Posten die in den nächsten 3 Monaten fällig werden.</p>');


output('<p style="margin: 1em; padding: 1em; border: 2px solid red; background: white;"><strong>Hinweis:</strong> Die hier aufgeführten Posten dienen nur Ihrer Information und erheben keinen Anspruch auf Vollständigkeit. Aus technischen Gründen sind manche Posten hier nicht aufgeführt, die dennoch berechnet werden. Zudem können, bedingt durch Rundungsfehler, die Beträge auf dieser Seite falsch dargestellt sein. Wiederkehrende Beträge werden grundsätzlich nur für den nächsten Abrechnungszeitraum angezeigt.</p>');

$items = upcoming_items();
$summe = 0;

$flip = true;
$today = date('Y-m-d');
$max_date = date('Y-m-d', strtotime('+3 months'));
output('<table><tr><th>Anzahl</th><th>Beschreibung</th><th>Zeitraum</th><th>Einzelpreis</th><th>Gesamtbetrag</th></tr>');

$counter = 0;

$more = false;
$odd = false;

foreach ($items as $item) {
    if ($item['startdatum'] > $max_date) {
        $more = true;
        break;
    }
    if ($flip && $item['startdatum'] > $today) {
        if ($counter == 0) {
            output("<tr class=\"even\"><td colspan=\"5\"><em>Aktuell keine fälligen Posten</em></td></tr>");
        }
        $flip = false;
        $odd = false;
        output("<tr class=\"even\"><td colspan=\"4\" style=\"text-align: right; font-weight: bold; border: none;\">Summe bisher fällige Posten:</td>");
        output("<td class=\"number\" style=\"font-weight: bold;\">{$summe} €</td></tr>\n");
        output("<tr><td colspan=\"5\" style=\"border: none;\"> </td></tr>\n");
    }
    $counter++;
    $desc = $item['startdatum'];
    if ($item['enddatum'] != null) {
        $desc = $item['startdatum'].' - '.$item['enddatum'];
    }
    $epreis = $item['betrag'];
    if ($item['brutto'] == 0) {
        $epreis = $epreis * (1 + ($item['mwst'] / 100));
    }
    $gesamt = round($epreis * $item['anzahl'], 2);
    $epreis = round($epreis, 2);
    $summe += $gesamt;
    $einheit = ($item['einheit'] ? $item['einheit'] : '');
    $class = ($odd ? "odd" : "even");
    $odd = !$odd;
    output("<tr class=\"$class\"><td class=\"number\">{$item['anzahl']} {$einheit}</td>");
    output("<td>{$item['beschreibung']}</td><td>{$desc}</td>");
    output("<td class=\"number\">{$epreis} €</td><td class=\"number\">{$gesamt} €</td></tr>\n");
}

if ($counter) {
    output("<tr class=\"even\"><td colspan=\"4\" style=\"text-align: right; font-weight: bold; border: none;\">Summe aller Posten:</td>");
    output("<td style=\"font-weight: bold;\" class=\"number\">{$summe} €</td></tr>\n");
    output('</table>');
} else {
    output("<tr class=\"even\"><td colspan=\"5\"><em>Es sind keine Posten hinterlegt, die in den nächsten 3 Monaten fällig werden.</em></td></tr></table>");
}

if ($more) {
    output('<p>'.internal_link('upcoming', other_icon('control_fastforward.png').' Alle zukünftigen Rechnungsposten anzeigen').'</p>');
}

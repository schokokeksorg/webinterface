<?php

require_role(ROLE_SYSADMIN);

$title = 'Report';


$year = date("Y") - 1;
if (isset($_GET['year'])) {
    $year = (int) $_GET['year'];
}

$typeresult = db_query("SELECT id, description, investment FROM buchhaltung.types");
$dataresult = db_query("SELECT id, date, description, invoice_id, direction, type, amount, tax_rate, gross FROM buchhaltung.transactions WHERE date BETWEEN :from and :to ORDER BY date", [":from" => $year . "-01-01", ":to" => $year . "-12-31"]);

$types = [];
$data_by_type = [];
$sum_by_type = [];
$investment_types = [];
while ($t = $typeresult->fetch()) {
    $types[$t['id']] = $t['description'];
    $data_by_type[$t['id']] = [];
    $sum_by_type[$t['id']] = 0.0;
    if ($t['investment'] == 1) {
        $investment_types[$t['id']] = $t;
    }
}

while ($line = $dataresult->fetch()) {
    $data_by_type[$line['type']][] = $line;
}


output("Journal für $year (01.01.$year-31.12.$year, gruppiert nach Buchungskonten)");

DEBUG($types);
DEBUG($investment_types);
$net_by_type = [0 => [-1 => [], 0 => [], 19 => []]];
$umsatzsteuer = 0.0;
$ustbetraege = [];
$vorsteuer = 0.0;
foreach ($types as $id => $t) {
    if (count($data_by_type[$id]) == 0 || $t == 'Privatentnahme') {
        continue;
    }
    output("<h3>$t</h3>");
    output('<table style="font-size: 10pt;">');
    $umsatz19proz = 0.0;
    $umsatz0proz = 0.0;
    $umsatzandereproz = 0.0;
    $netsum = 0.0;
    $ustsum = 0.0;
    foreach ($data_by_type[$id] as $line) {
        $net = $line['amount'];
        if ($line['gross'] == 1 && $line['tax_rate'] > 0) {
            $net = $net / (1.0 + ($line['tax_rate'] / 100));
        }
        if ($line['direction'] == 'out') {
            $net = -$net;
        }
        $ust = $net * ($line['tax_rate'] / 100);
        if ($line['tax_rate'] == 19.0) {
            $umsatz19proz += $net;
        } elseif ($line['tax_rate'] == 0.0) {
            $umsatz0proz += $net;
        } else {
            $umsatzandereproz += $net;
        }
        $netsum += $net;
        $ustsum += $ust;
        if ($id == 0) {
            if (!isset($ustbetraege[$line['tax_rate']])) {
                $ustbetraege[$line['tax_rate']] = 0;
            }
            $ustbetraege[$line['tax_rate']] += $ust;
            $umsatzsteuer += $ust;
        } else {
            $vorsteuer += $ust;
        }
        $gross = $net + $ust;
        $net = str_replace('.', ',', sprintf('%.2f €', $net));
        $ust = str_replace('.', ',', sprintf('%.2f €', $ust));
        $gross = str_replace('.', ',', sprintf('%.2f €', $gross));
        output("<tr><td>" . $line['date'] . "</td><td>" . $line['description'] . "</td><td style=\"text-align: right;\">" . $net . "</td><td style=\"text-align: right;\">" . $line['tax_rate'] . "%</td><td style=\"text-align: right;\">" . $ust . "</td><td style=\"text-align: right;\">" . $gross . "</td></tr>\n");
    }
    if ($id == 0) {
        $net_by_type[0][-1] = $umsatzandereproz;
        $net_by_type[0][0] = $umsatz0proz;
        $net_by_type[0][19] = $umsatz19proz;
    } else {
        $net_by_type[$id] = $netsum;
    }
    $netsum = str_replace('.', ',', sprintf('%.2f €', $netsum));
    $ustsum = str_replace('.', ',', sprintf('%.2f €', $ustsum));
    output("<tr><td colspan=\"2\" style=\"font-weight: bold;text-align: right;\">Summe $t:</td><td style=\"font-weight: bold;text-align: right;\">$netsum</td><td></td><td style=\"font-weight: bold;text-align: right;\">$ustsum</td><td></td></tr>\n");
    output('</table>');
}

output("<h3>Summen</h3>");

output('<table>');
$einnahmensumme = 0.0;
output("<tr><td>Einnahmen 19% USt netto</td><td style=\"text-align: right;\">" . number_format($net_by_type[0][19], 2, ',', '.') . " €</td></tr>");
$einnahmensumme += $net_by_type[0][19];
output("<tr><td>Einnahme Umsatzsteuer 19%</td><td style=\"text-align: right;\">" . number_format($ustbetraege[19], 2, ',', '.') . " €</td></tr>");
$einnahmensumme += $ustbetraege[19];
output("<tr><td>Einnahmen innergem. Lieferung (steuerfrei §4/1b UStG)</td><td style=\"text-align: right;\">" . number_format($net_by_type[0][0], 2, ',', '.') . " €</td></tr>");
$einnahmensumme += $net_by_type[0][0];
output("<tr><td>Einnahmen andere Steuersätze</td><td style=\"text-align: right;\">" . number_format($net_by_type[0][-1], 2, ',', '.') . " €</td></tr>");
$einnahmensumme += $net_by_type[0][-1];
$einzelust = '';
foreach ($ustbetraege as $satz => $ust) {
    if ($satz == 0 || $satz == 19) {
        continue;
    }
    output("<tr><td>- Umsatzsteuer $satz%</td><td style=\"text-align: right;\">" . number_format($ust, 2, ',', '.') . " €</td></tr>");
    $einnahmensumme += $ust;
}

output("<tr><td><b>Summe Einnahmen:</b></td><td style=\"text-align: right;\"><b>" . number_format($einnahmensumme, 2, ',', '.') . " €</td></tr>");
output("<tr><td colspan=\"2\"></td></tr>");
$ausgabensumme = 0.0;
foreach ($types as $id => $t) {
    if ($t == 'Gewerbesteuer') {
        continue;
    }
    if ($id == 0 || !isset($net_by_type[$id]) || array_key_exists($id, $investment_types)) {
        continue;
    }
    $ausgabensumme -= round($net_by_type[$id], 2);
    output("<tr><td>" . $t . "</td><td style=\"text-align: right;\">" . number_format(-$net_by_type[$id], 2, ',', '.') . " €</td></tr>");
}

output("<tr><td>Vorsteuer</td><td style=\"text-align: right;\">" . number_format(-$vorsteuer, 2, ',', '.') . " €</td></tr>");
$ausgabensumme -= $vorsteuer;
output("<tr><td><b>Summe Ausgaben:</b></td><td style=\"text-align: right;\"><b>" . number_format($ausgabensumme, 2, ',', '.') . " €</td></tr>");
output("<tr><td colspan=\"2\"></td></tr>");

output("<tr><td><b>Überschuss aus laufendem Betrieb:</b></td><td style=\"text-align: right;\"><b>" . number_format($einnahmensumme - $ausgabensumme, 2, ',', '.') . " €</td></tr>");
output('</table>');

foreach ($investment_types as $id => $type) {
    if (isset($net_by_type[$id])) {
        output('<p>Neue Anlagegüter <strong>' . $type['description'] . '</strong>: ' . number_format(-$net_by_type[$id], 2, ',', '.') . " €</p>");
    }
}

<?php

require_role(ROLE_SYSADMIN);

$title = 'Report';


$year = date("Y")-1;

$typeresult = db_query("SELECT id, description FROM buchhaltung.types");
$dataresult = db_query("SELECT id, date, description, invoice_id, direction, type, amount, tax_rate, gross FROM buchhaltung.transactions WHERE date BETWEEN :from and :to ORDER BY date", array(":from" => $year."-01-01", ":to" => $year."-12-31"));

$types = array();
$data_by_type = array();
$sum_by_type = array();
while ($t = $typeresult->fetch()) {
    $types[$t['id']] = $t['description'];
    $data_by_type[$t['id']] = array();
    $sum_by_type[$t['id']] = 0.0;
}

while ($line = $dataresult->fetch()) {
    $data_by_type[$line['type']][] = $line;
}


output("Journal für $year (01.01.$year-31.12.$year, gruppiert nach Buchungskonten)");

DEBUG($types);
$net_by_type = array(0 => array(-1 => array(), 0 => array(), 19 => array()));
$umsatzsteuer = 0.0;
$vorsteuer = 0.0;
foreach ($types as $id => $t) {
    if (count($data_by_type[$id]) == 0) {
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
            $net = $net / (1.0+($line['tax_rate']/100));
        }
        if ($line['direction'] == 'out') {
            $net = -$net;
        }
        $ust = $net * ($line['tax_rate']/100);
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
            $umsatzsteuer += $ust;
        } else {
            $vorsteuer += $ust;
        }
        $gross = $net + $ust;
        $net = str_replace('.', ',', sprintf('%.2f €', $net));
        $ust = str_replace('.', ',', sprintf('%.2f €', $ust));
        $gross = str_replace('.', ',', sprintf('%.2f €', $gross));
        output("<tr><td>".$line['date']."</td><td>".$line['description']."</td><td style=\"text-align: right;\">".$net."</td><td style=\"text-align: right;\">".$ust."</td><td style=\"text-align: right;\">".$gross."</td></tr>\n");
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
    output("<tr><td colspan=\"2\" style=\"font-weight: bold;text-align: right;\">Summe $t:</td><td style=\"font-weight: bold;text-align: right;\">$netsum</td><td style=\"font-weight: bold;text-align: right;\">$ustsum</td><td></td></tr>\n");
    output('</table>');
}

output("<h3>Summen</h3>");

output('<table>');
$einnahmensumme = 0.0;
output("<tr><td>Einnahmen 19% USt netto</td><td style=\"text-align: right;\">".number_format($net_by_type[0][19], 2, ',', '.')." €</td></tr>");
$einnahmensumme += $net_by_type[0][19];
output("<tr><td>Einnahmen innergem. Lieferung (steuerfrei §4/1b UStG)</td><td style=\"text-align: right;\">".number_format($net_by_type[0][0], 2, ',', '.')." €</td></tr>");
$einnahmensumme += $net_by_type[0][0];
output("<tr><td>Einnahmen EU-Ausland (VATMOSS)</td><td style=\"text-align: right;\">".number_format($net_by_type[0][-1], 2, ',', '.')." €</td></tr>");
$einnahmensumme += $net_by_type[0][-1];
output("<tr><td>Einnahme Umsatzsteuer</td><td style=\"text-align: right;\">".number_format($umsatzsteuer, 2, ',', '.')." €</td></tr>");
$einnahmensumme += $umsatzsteuer;

output("<tr><td><b>Summe Einnahmen:</b></td><td style=\"text-align: right;\"><b>".number_format($einnahmensumme, 2, ',', '.')." €</td></tr>");
output("<tr><td colspan=\"2\"></td></tr>");
$ausgabensumme = 0.0;
foreach ($types as $id => $t) {
    if ($id == 0 || !isset($net_by_type[$id])) {
        continue;
    }
    $ausgabensumme -= $net_by_type[$id];
    output("<tr><td>".$t."</td><td style=\"text-align: right;\">".number_format(-$net_by_type[$id], 2, ',', '.')." €</td></tr>");
}

output("<tr><td>Vorsteuer</td><td style=\"text-align: right;\">".number_format(-$vorsteuer, 2, ',', '.')." €</td></tr>");
$ausgabensumme -= $vorsteuer;
output("<tr><td><b>Summe Ausgaben:</b></td><td style=\"text-align: right;\"><b>".number_format($ausgabensumme, 2, ',', '.')." €</td></tr>");
output("<tr><td colspan=\"2\"></td></tr>");

output("<tr><td><b>Überschuss aus laufendem Betrieb:</b></td><td style=\"text-align: right;\"><b>".number_format($einnahmensumme-$ausgabensumme, 2, ',', '.')." €</td></tr>");
output('</table>');


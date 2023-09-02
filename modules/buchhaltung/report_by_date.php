<?php

require_role(ROLE_SYSADMIN);

$title = 'Report';


$year = date("Y") - 1;

$typeresult = db_query("SELECT id, description FROM buchhaltung.types");
$dataresult = db_query("SELECT id, date, description, invoice_id, direction, type, amount, tax_rate, gross FROM buchhaltung.transactions WHERE date BETWEEN :from and :to ORDER BY date", [":from" => $year."-01-01", ":to" => $year."-12-31"]);

$types = [];
$data = [];
while ($t = $typeresult->fetch()) {
    $types[$t['id']] = $t['description'];
}

while ($line = $dataresult->fetch()) {
    $data[] = $line;
}


output("Journal für $year (01.01.$year-31.12.$year, sortiert nach Datum)");
output("<h3>$t</h3>");
output("<table>");

foreach ($data as $line) {
    $net = $line['amount'];
    if ($line['gross'] == 1 && $line['tax_rate'] > 0) {
        $net = $net / (1.0 + ($line['tax_rate'] / 100));
    }
    if ($line['direction'] == 'out') {
        $net = -$net;
    }
    $ust = $net * ($line['tax_rate'] / 100);
    $gross = $net + $ust;
    $net = str_replace('.', ',', sprintf('%.2f €', $net));
    $ust = str_replace('.', ',', sprintf('%.2f €', $ust));
    $gross = str_replace('.', ',', sprintf('%.2f €', $gross));
    $typetext = $types[$line['type']];
    output("<tr><td>".$line['date']."</td><td>".$typetext."</td><td>".$line['description']."</td><td style=\"text-align: right;\">".$net."</td><td style=\"text-align: right;\">".$ust."</td><td style=\"text-align: right;\">".$gross."</td></tr>\n");
}

output('</table>');

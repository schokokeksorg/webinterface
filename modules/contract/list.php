<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2018 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('contract.php');
require_once('inc/debug.php');
require_once('inc/icons.php');

require_role(array(ROLE_CUSTOMER));

title("AV-Vertrag");


output('<p>Sofern Sie über Ihre Website oder durch Ihr E-Mail-Postfach Daten von Dritten erheben, verarbeiten oder auf dem von uns bereitgestellten Speicherplatz solche Daten ablegen, müssen Sie möglicherweise einen Vertrag zur Auftragsverarbeitung mit uns abschließen. Ob Sie dazu gemäß der DS-GVO verpflichtet sind, kann im Zweifel eine individuelle Rechtsberatung in Ihrem Unternehmen beantworten.</p>
<p>Der von uns angebotene Vertrag zur Auftragverarbeitung gibt Ihnen hierzu Rechtssicherheit. Da wir schon immer das Prinzip der Datensparsamkeit anwenden und unsere Abläufe darauf und auf höchstmöglicher Sicherheit aufbauen, verändert dieser Vertrag unsere Abläufe nicht wesentlich. Ihre Pflichten aus dem Vertrag begrenzen sich maßgeblich auf die Klärung der Zuständigkeit bzgl. der Rechte der betroffenen Personen.</p>

');

$contract = get_orderprocessing_contract();
if ($contract) {
    $sign = date('d.m.Y', strtotime($contract['signed']));
    output('<p>Sie haben am <strong>'.$sign.'</strong> einen Vertrag zur Auftragsverarbeitung mit uns abgeschlossen.</p>
    <p>'.internal_link('download', 'Vertrag als PDF herunterladen', "id={$contract['id']}").'</p>');
    output('<p>Wenn Sie Änderungen oder eine Auflösung dieses Vertrags wünschen, wenden Sie sich bitte an den Support.</p>');
} else {
    addnew('new_op', 'Einen Vertrag zur Auftragsverarbeitung abschließen');
}

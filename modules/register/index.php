<?php

/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

//require_once('inc/error.php');
//system_failure("Diese Funktion ist noch nicht fertiggestellt.");

require_once('newpass.php');

require_once('inc/form.php');
require_once('inc/base.php');

$fail = [];
$success = false;
$customerno = 0;

title("schokokeks.org testen");
headline("schokokeks.org unverbindlich testen");

output("<p>Da wir bei schokokeks.org Hosting immer auf volle Transparenz und Kundenzufriedenheit setzen, können Sie unser Angebot unverbindlich und in (beinahe) vollem Umfang testen. Funktionen, die zusätzliche Kosten verursachen (z.B. Domainregistrierungen) sind im Testaccount nicht möglich.</p>
<p>Mit Ausfüllen dieses Formulars können Sie sich einen Test-Zugang einrichten, den Sie 4 Tage lang nutzen können. Sollten Sie mit uns zufrieden sein, können Sie den Zugang jederzeit und ohne Verlust von bisherigen Einstellungen oder Daten in einen normalen Zugang umwandeln.</p>
<p><strong>Wichtig:</strong> Um uns gegenüber Spassanmeldungen abzusichern, benötigt dieses automatisierte Verfahren Ihre Handynummer. Haben Sie kein Handy oder möchten Sie uns Ihre Handynummer nicht geben, können Sie auch über unseren E-Mail-Support einen gleichwertigen Test-Zugang anfordern.</p>");


$form = '<p>' . label('mobile', 'Handynummer:') . ' ' . textinput('mobile') . '</p>
<p>' . checkbox('terms', 'yes', false, 'Ich habe die <a href="https://schokokeks.org/agb">AGB</a> gelesen und erkläre hiermit meine Absicht, einen Zugang bei schokokeks.org unverbindlich zum Test des Angebots anzufordern.') . '</p>
<p>Sie erhalten nach dem Anfordern des Zugangs eine SMS auf Ihre angegebene Handynummer. Diese SMS enthält einen Code, den Sie auf der folgenden Seite eingeben müssen. Nach Eingabe des Codes wird Ihr Zugang umgehend freigeschaltet.</p>
<p>' . submit('Testzugang anfordern') . '</p>';


output(html_form('register_step1', 'step1_save', '', $form));

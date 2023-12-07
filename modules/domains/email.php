<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/debug.php');
require_once('inc/icons.php');

require_once('class/domain.php');
require_once('domains.php');

require_role([ROLE_CUSTOMER, ROLE_SYSTEMUSER]);

$dom = null;
if (isset($_REQUEST['dom'])) {
    $dom = new Domain((int) $_REQUEST['dom']);
} else {
    system_failure("Keine Domain angegeben");
}
if (have_role(ROLE_CUSTOMER)) {
    $dom->ensure_customerdomain();
} else {
    $dom->ensure_userdomain();
}

title("E-Mail-Einstellungen für {$dom->fqdn}");
$section = 'domains_email';

if (!have_module('email')) {
    system_failure("email settings not available");
}

if ($dom->provider != 'terions') {
    $mxresult = dns_get_record($dom->fqdn, DNS_MX);
    $found = false;
    foreach ($mxresult as $mx) {
        if (substr_compare($mx['target'], config('masterdomain'), -strlen(config('masterdomain'))) === 0) {
            $found = true;
        }
    }
    if (! $found) {
        DEBUG('MX für '.$dom->fqdn.':');
        DEBUG($mxresult);
        warning('Bei dieser Domain ist der Mail-Empfang aktiviert, jedoch verweist das DNS-System scheinbar nicht auf unsere Anlagen. Wenn Sie keine E-Mails empfangen möchten, schalten Sie die Mail-Verarbeitung für diese Domain aus.');
    }
}

output('<p>Die Verarbeitung eingehender E-Mail kann bei schokokeks.org auf zwei unterschiedliche Weisen erfolgen.<p>
<ol><li>Sie können mit der <strong>Webinterface-Verwaltung</strong> einfache E-Mail-Konten erstellen, die ankommende E-Mails speichern oder weiterleiten.</li>
<li>Sie können die manuelle Verwaltung wählen, bei der Sie passende .courier-Dateien für den Empfang und manuelle POP3/IMAP-Konten für den Abruf selbst verwalten.</li></ol>
<p>Eine parallel Nutzung beider Verfahren mit der selben Domain ist nicht möglich. Wenn Sie eine Domain auf Webinterface-Verwaltung einrichten, dann werden eventuell vorhandene .courier-Dateien nicht mehr beachtet.</p>
<p>Der Mail-Empfang auf Subdomains muss grundsätzlich durch Administratoren eingerichtet und verändert werden.</p>');

output('<h4>Aktuelle Einstellung</h4>');
$setting = mail_setting($dom->id);
if ($setting == 'none') {
    output('<div class="error">E-Mail-Empfang abgeschaltet</div>
    <p>Aktuell ist der Empfang von E-Mail für die Domain <strong>'.$dom->fqdn.'</strong> ausgeschaltet.</p>
    <ul>
    <li>Die E-Mail-spezifischen DNS-Records wie z.B. MX, SPF, DKIM und Autoconfig werden nicht erstellt.</li>
    <li>Sie können keine Postfächer unter der Domain anlegen.</li>
    <li>Der Mail-Server wird E-Mails an diese Domain nicht annehmen.</li>
    </ul>');
    addnew('email_save', 'Mail-Empfang einschalten (Webinterface-Verwaltung)', "dom=".$dom->id."&mail=vmail");
    addnew('email_save', 'Mail-Empfang einschalten (Manuelle Verwaltung)', "dom=".$dom->id."&mail=manual", 'class="grey"');
} elseif ($setting == 'vmail') {
    output('<div class="success">E-Mail-Empfang eingeschaltet (Webinterface-Verwaltung)</div>
    <p>Aktuell können Sie Ihre Postfächer ganz einfach über unser Webinterface verwalten. Dies ist die Standardeinstellung.</p>
    <ul>
    <li>Nachrichten werden vom Server angenommen, sofern die zugehörige Adresse eingerichtet ist.</li>
    <li>Die DNS-Records (z.B. MX, SPF, DKIM und Autoconfig) werden erstellt, sofern Sie keinen dazu widersprüchlichen DNS-Record selbst angelegt haben.</li>
    <li>Um neue Postfächer und Weiterleitungen für diese Domain anzulegen, besuchen Sie bitte den Bereich "E-Mail" in diesem Webinterface.</li>
    </ul>');
    if (count_vmail($dom->id) > 0) {
        output('<p>So lange noch E-Mail-Adressen unter dieser Domain eingerichtet sind, können Sie diese Einstellung nicht ändern.</p>');
    } else {
        output('<p class="delete">'.internal_link("email_save", "Mail-Empfang für diese Domain ausschalten", "dom=".$dom->id.'&mail=none').'</p>');
    }
} elseif ($setting == 'manual') {
    output('<div class="warning">E-Mail-Empfang aktiv (Manuelle Verwaltung)</div>
    <p>Für diese Domain müssen Sie selbst die passenden .courier-Dateien verwalten, damit die Mails zugestellt werden.</p>
    <ul>
    <li>Nachrichten werden vom Server angenommen, sofern die zugehörige Adresse eingerichtet ist.</li>
    <li>Die DNS-Records (z.B. MX, SPF, DKIM und Autoconfig) werden erstellt, sofern Sie keinen dazu widersprüchlichen DNS-Record selbst angelegt haben.</li>
    <li>Hilfestellung zu den damit verbundenen Möglichkeiten erhalten Sie <a href="https://wiki.schokokeks.org/E-Mail/Manuelle_Konfiguration">in unserem Wiki</a></li>
    </ul>');
    output('<p class="delete">'.internal_link("email_save", "Mail-Empfang für diese Domain ausschalten", "dom=".$dom->id.'&mail=none').'</p>');
} else {
    system_failure('unbekannter Zustand der Domain');
}






output('<p>'.internal_link('domains', 'Ohne Änderungen zurück').'</p>');

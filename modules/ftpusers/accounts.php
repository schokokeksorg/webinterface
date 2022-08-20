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
require_role(ROLE_SYSTEMUSER);

include("ftpusers.php");

$ftpusers = list_ftpusers();
$regular_ftp = have_regular_ftp();


title("Zusätzliche FTP-Benutzer");
output('
<p>Als Zusatzleistung bieten wir Ihnen die Möglichkeit, weitere FTP-Benutzerzugänge anzulegen. Diese Zugänge sind auf das angegebene Verzeichnis beschränkt und es kann nur mit dem FTP-Protokoll verwendet werden. Ein Login mittels SSH ist für diese Benutzerkonten nicht möglich.</p>');


if (count($ftpusers) > 0) {
    output('
<table><tr><th>Benutzername</th><th>Verzeichnis</th><th>aktiv</th><th>&#160;</th></tr>
');

    foreach ($ftpusers as $f) {
        $sslwarning = ($f['forcessl'] == 0 ? icon_warning('Unverschlüsselte Verbindungen werden erlaubt') : '');
        $active = ($f['active'] == 1 ? icon_enabled('Ja') : '-');
        output("<tr><td>".internal_link("edit?id={$f['id']}", $f['username'])."</td><td>{$f['homedir']}</td><td style=\"text-align: center;\">{$active} {$sslwarning}</td><td>".internal_link("save?delete={$f['id']}", icon_delete("{$f['username']} löschen"))."</td></tr>");
    }
    output('</table>');
} else {
    output('<p><em>Sie haben bisher keine zusätzlichen FTP-Benutzer angelegt</em></p>');
}

addnew('edit', 'Neuen FTP-Benutzer anlegen');


output('<h3>Haupt-Account mittels FTP nutzen</h3>
<p>Wenn Sie aus einem bestimmten Grund nicht mit den bei uns üblichen Datei-Übertragungsverfahren arbeiten können, besteht hier die Möglichkeit zusätzlich auch FTP-Zugriff für Ihren Benutzeraccount einzurichten.</p>
<p>Normalerweise laden Sie Ihre Dateien per SFTP bzw. SCP auf den Server. Nur wenn dies in Ihrem Fall nicht möglich ist, benötigen Sie FTP.</p>
');

$token = generate_form_token('regular_ftp');

if ($regular_ftp) {
    output('<p>'.icon_enabled().' Momentan ist der Zugriff über FTP <strong>aktiviert</strong>. Wenn Sie diesen nicht benötigen sollten Sie ihn aus Sicherheitsgründen ausschalten.<br />'.internal_link('save', 'FTP-Zugriff für Haupt-Account sperren', 'regular_ftp=no&token='.$token).'</p>');
} else {
    output('<p>Der Zugriff Ihres Haupt-Accounts über FTP ist momentan abgeschaltet. Aktivieren Sie diesen nur wenn Sie ihn auch nutzen möchten.<br />'.internal_link("save", 'FTP-Zugriff für Haupt-Account freischalten', 'regular_ftp=yes&token='.$token).'</p>');
}

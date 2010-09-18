<?php

require_once('inc/base.php');
require_once('inc/icons.php');
require_role(ROLE_SYSTEMUSER);

include("ftpusers.php");

$ftpusers = list_ftpusers();
$regular_ftp = have_regular_ftp();


title("Zusätzliche FTP-Benutzer");
output('
<p>Als Zusatzleistung bieten wir Ihnen die Möglichkeit, weitere FTP-Benutzerzugänge anzulegen. Diese Zugänge sind auf das angegebene Verzeichnis beschränkt und es kann nur mit dem FTP-Protokoll verwendet werden. Ein Login mittels SSH ist für diese Benutzerkonten nicht möglich.</p>');


if (count($ftpusers) > 0)
{
  output('
<table><tr><th>Benutzername</th><th>Verzeichnis</th><th>aktiv</th><th>&#160;</th></tr>
');

  foreach ($ftpusers AS $f)
  {
    $active = ($f['active'] == 1 ? icon_enabled('Ja') : '-');
    output("<tr><td>".internal_link("edit?id={$f['id']}", $f['username'])."</td><td>{$f['homedir']}</td><td style=\"text-align: center;\">{$active}</td><td>".internal_link("save?delete={$f['id']}", icon_delete("{$f['username']} löschen"))."</td></tr>");
  }
  output('</table>');
}
else
{
  output('<p><em>Sie haben bisher keine zusätzlichen FTP-Benutzer angelegt</em></p>');
}

addnew('edit', 'Neuen FTP-Benutzer anlegen');


output('<h3>Haupt-Account mittels FTP nutzen</h3>
<p>Durch den Zugriff mittels SSH bzw. SFTP können Sie sämtliche Ihrer Dateien bearbeiten und alle Funktionen nutzen, die wir Ihnen bereitstellen. Wenn sie möchten, können Sie sich mit diesen Zugangsdaten zusätzlich auch über das FTP-Protokoll verbinden. Diese Funktion ist für die Nutzbarkeit der obigen, zusätzlichen FTP-Accounts nicht erforderlich.</p>
');

$token = generate_form_token('regular_ftp');

if ($regular_ftp)
{
  output('<p>'.icon_enabled().' Momentan ist der Zugriff über FTP aktiviert. Wenn Sie diesen nicht benötigen sollten Sie ihn aus Sicherheitsgründen ausschalten.<br />'.internal_link('save', 'FTP-Zugriff für Haupt-Account sperren', 'regular_ftp=no&token='.$token).'</p>');
}
else
{
  output('<p>'.icon_error().' Der Zugriff Ihres Haupt-Accounts über FTP ist momentan abgeschaltet. Aktivieren Sie diesen nur wenn Sie ihn auch nutzen möchten.<br />'.internal_link("save", 'FTP-Zugriff für Haupt-Account freischalten', 'regular_ftp=yes&token='.$token).'</p>');
}

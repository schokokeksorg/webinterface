<?php

require_once('inc/base.php');
require_once('inc/icons.php');

include("ftpusers.php");

$ftpusers = list_ftpusers();
$regular_ftp = have_regular_ftp();


output('<h3>FTP-Zugänge</h3>
<p>Mit Ihrem SSH- bzw. SFTP-Zugriff können Sie sämtliche Ihrer Dateien bearbeiten und alle Funktionen nutzen, die wir Ihnen bereitstellen. Wenn sie möchten, können Sie sich mit diesen Zugangsdaten auch über das FTP-Protokoll verbinden.</p>
');

if ($regular_ftp)
{
  output('<p>'.icon_enabled().' Momentan ist der Zugriff über FTP aktiviert. Wenn Sie diesen nicht benötigen sollten Sie ihn aus Sicherheitsgründen ausschalten.<br /><a href="edit?regular_ftp=no">FTP-Zugriff sperren</a></p>');
}
else
{
  output('<p>'.icon_error().' Der Zugriff über FTP ist momentan gesperrt. Aktivieren Sie diesen nur wenn Sie ihn auch nutzen möchten.<br /><a href="edit?regular_ftp=yes">FTP-Zugriff freischalten</a></p>');
}

output('
<h3>Zusätzliche FTP-Benutzer</h3>
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


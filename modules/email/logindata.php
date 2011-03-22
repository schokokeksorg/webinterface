<?php
require_once('inc/base.php');

require_role(ROLE_SYSTEMUSER);

$section='email_vmail';
title("Einstellungen zum E-Mail-Abruf");

$servername = filter_input_hostname($_REQUEST['server']);
$type = 'vmail';
if (isset($_REQUEST['type']) && $_REQUEST['type'] == 'manual') {
  $type = 'manual';
}

output("<p>Sie können bei schokokeks.org Hosting Ihre E-Mails wahlweise direkt im Browser mit einem Web-Mail-System lesen oder mit einem E-Mail-Programm auf Ihrem Rechner per POP3 oder IMAP abrufen. Im folgenden möchten wir Ihnen erklären, wie Sie diese Möglichkeiten nutzen können.</p>");

output('<h3>Lesen per Web-Mail</h3>
<p>Die Anmeldung zu unseren Web-Mail-Systemen erfolgt unter</p>
<p style="margin-left: 2em; font-size: 130%; font-weight: bold;"><a href="https://mail.schokokeks.org/">mail.schokokeks.org</a></p>');
if ($type == 'manual') {
  output('<p>Dort geben Sie bitte im Feld für die E-Mail-Adresse Ihren Account-Namen ein.</p>');
} else {
  output('<p>Dort geben Sie bitte Ihre E-Mail-Adresse und das dazu gehörige Passwort ein um sich anzumelden.</p>');
}

output('<h3>Abruf mit einem E-Mail-Programm</h3>
<p>Wenn Sie ein E-Mail-Programm auf Ihrem Computer (wie z.B. Mozilla Thunderbird) zum Abruf benutzen möchten, haben Sie die Wahl zwischen POP3 und IMAP. Ihre Zugangsdaten lassen sich mit beiden Technologien benutzen.</p>
<div style="width: 20%; margin-right: 2em; float: left;">
<h3 style="text-align: center;">IMAP</h3>
<p style="text-align: justify;">Bei IMAP werden die E-Mails dauerhaft <strong>auf dem Server gespeichert</strong>. Das E-Mail-Programm läd (je nach Einstellung) nur die Kopfzeilen und die jeweils angeschaute E-Mail herunter. Bei IMAP können Sie <strong>Unterordner</strong> in Ihrem Postfach haben. Da die Mails auf dem Server gespeichert sind, können Sie jederzeit mit einem <strong>Web-Mail-System</strong> auch auf gelesene E-Mails zugreifen. Die gespeicherten E-Mails können allerdings dazu führen, dass Ihr Speicherplatz schneller verbraucht ist.</p>
<p>Die Einstellungen für IMAP:</p>
<dl>
<dt>Protokoll</dt><dd>IMAP</dd>
<dt>Servername</dt><dd>'.$servername.'</dd>
<dt>Port</dt><dd>993</dd>
<dt>Verschlüsselung</dt><dd>SSL (nicht STARTTLS, TLS oder ähnliches)</dd>
<dt>Benutzername</dt><dd><em>'.($type=='manual'? 'Ihr Account-Name' : 'Ihre E-Mail-Adresse').'</em></dd>
<dt>Passwort</dt><dd><em>Ihr E-Mail-Passwort</em></dd>
</dl>
</div>
<div style="width: 20%; margin-right: 2em; float: left;">
<h3 style="text-align: center;">POP3</h3>
<p style="text-align: justify;">Bei POP3 werden die E-Mails auf Ihren Computer herunter geladen und anschließend (je nach Einstellung) <strong>auf dem Server gelöscht</strong>. Sie können mit einem Web-Mail-System von unterwegs nur die E-Mails lesen, die noch nicht von Ihrem E-Mail-Programm abgerufen worden sind.</p>
<p>Die Einstellungen für POP3:</p>
<dl>
<dt>Protokoll</dt><dd>POP3</dd>
<dt>Servername</dt><dd>'.$servername.'</dd>
<dt>Port</dt><dd>995</dd>
<dt>Verschlüsselung</dt><dd>SSL (nicht STARTTLS, TLS oder ähnliches)</dd>
<dt>Benutzername</dt><dd><em>'.($type=='manual'? 'Ihr Account-Name' : 'Ihre E-Mail-Adresse').'</em></dd>
<dt>Passwort</dt><dd><em>Ihr E-Mail-Passwort</em></dd>
</dl>
</div>
<br style="clear: left;" />
<h3>SMTP</h3>
<p>Zum Verschicken von E-Mails muss sich Ihr E-Mail-Programm auch per SMTP anmelden. Benutzen Sie dafür bitte die folgenden Daten:</p>
<dt>Protokoll</dt><dd>SMTP</dd>
<dt>Servername</dt><dd>'.$servername.'</dd>
<dt>Port</dt><dd>587</dd>
<dt>Verschlüsselung</dt><dd>TLS (oder STARTTLS)</dd>
<dt>Benutzername</dt><dd><em>'.($type=='manual'? 'Ihr Account-Name' : 'Ihre E-Mail-Adresse').'</em></dd>
<dt>Passwort</dt><dd><em>Ihr E-Mail-Passwort</em></dd>
<br />

');








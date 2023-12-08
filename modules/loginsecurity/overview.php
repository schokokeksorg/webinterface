<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('passkey.php');
require_once('inc/icons.php');
require_role(ROLE_SYSTEMUSER);
require_once('inc/javascript.php');
javascript("passkey_ajax.js");

title("Sichere Anmeldung");

output('<p>Sie können die Anmeldung bei ' . config('company_name') . ' passwortlos einrichten.</p>');

output('<h3>Anmeldung mit Passkeys / FIDO2</h3>');

output('<p>Mit der Passkeys-Technologie (technisch identisch zu FIDO2 / WebAuthn) können Sie die Anmeldung mit einem Hardware-Security-Modul oder mit Ihrem Mobilgerät als Schlüssel verwenden (Die Möglichkeiten variieren je nach Betriebssystem bzw. Browser). Wir nutzen in diesem Webinterface den Begriff <em>Passkeys</em>, damit ist jedoch stets auch die Verwendung unterschiedlicher FIDO2-Geräte gemeint.</p>
<p>Bei der Anmeldung mit einem Passkey müssen Sie weder Benutzername noch Passwort eingeben sondern werden (nach Bestätigung Ihres Sicherheitsgeräts) direkt im Webinterface eingeloggt.</p>
<p><strong>Bitte beachten Sie:</strong> Wenn Sie hier einen Schlüssel hinterlegen, funktioniert diese Anmeldemethode nur hier im Webinterface. Für die Anmeldung per SSH müssen Sie für eine vergleichbare Sicherheit und Komfort einen SSH-Schlüssel in Ihrem Benutzeraccount hinterlegen. Je nach Ihrem Client-System kann auch der SSH-Key dann über das Sicherheitsmodul oder via Passkeys verwaltet werden.</p>');

output('<p>Zur Absicherung Ihres Zugangs empfehlen wir folgendes Vorgehen: Richten Sie den SSH-Zugang über einen SSH-Key und den Zugang zu diesem Webinterface über einen Passkey ein. Setzen Sie dann ein komplexes, neues Passwort zur Wiederherstellung im Fehlerfall und heben Sie dieses Passwort an einem sicheren Ort auf (z.B. ausgedruckt im Tresor). Schalten Sie dann den SSH-Login über Passwort ab. So steht Ihnen das Passwort als Sicherheit zur Wiederherstellung Ihres Zugangs in diesem Webinterface zu Verfügug, es wird aber im Alltag nicht gebraucht.</p>');

$passkeys = list_passkeys();
if (count($passkeys) > 0) {
    output('<h4>Aktuell eingerichtete Passkeys:</h4>');
    foreach ($passkeys as $pk) {
        $hostname = '';
        $rpId = $_SERVER['HTTP_HOST'];
        if ($pk['rpId'] != $rpId) {
            $hostname = 'Nur gültig für die URL <strong>' . $pk['rpId'] . '</strong>!<br>';
        }
        output("<p class=\"passkey\">Gerätebezeichnung: <strong>{$pk['handle']}</strong><br>hinzugefügt am {$pk['setuptime']}<br>" . $hostname . internal_link("delete_passkey", icon_delete() . "Diesen Passkey löschen", "id={$pk['id']}") . "</p>");
    }
}

output('<p><label for="passkey_handle">Bezeichnung für neuen Passkey:</label> <input id="passkey_handle" type="text" length="20"> <button onclick="passkey_register()">Passkey registrieren</button></p>
<p><button onclick="passkey_validate(false)">Passkey prüfen</button></p>');

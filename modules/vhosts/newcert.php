<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2014 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

include_once('certs.php');
require_role(ROLE_SYSTEMUSER);

$hint = '';
$oldcert = NULL;
if (isset($_REQUEST['replace']))
{
  title('Zertifikat ersetzen');

  $cert = cert_details($_REQUEST['replace']);
  $oldcert = $cert['id'];
  $hint = "<div style=\"border: 2px solid red; padding: 1em; margin: 1em;\"<p><strong>Hinweis:</strong> Dieses Zertifikat soll als Ersatz für ein bestehendes Zertifikat eingetragen werden. Dabei wird jede Benutzung des alten Zertifikats durch das neue ersetzt. Das alte Zertifikat wird dann umgehend gelöscht.<p>

<p><strong>Daten des alten Zertifikats:</strong></p>
<p><strong>CN:</strong> {$cert['cn']}<br /><strong>Gültigkeit:</strong> {$cert['valid_from']} - {$cert['valid_until']}</p></div>";

} else {
  title('Neues Server-Zertifikat hinzufügen');
}


$section = 'vhosts_certs';


output($hint.'
<h4>CSR automatisch erzeugen</h4>
<p>Mit unserem CSR-Generator können Sie einen Certificate-signing-request (CSR) automatisch erzeugen lassen. Nutzen Sie diese Möglichkeit bitte nur, wenn Sie ein so genanntes "Domain validated"-Zertifikat beantragen werden, das keine persönlichen Daten bzw. Firmendaten enthält.</p>');

if ($oldcert) {
  $cn = urlencode($cert['cn']);
  addnew('savecert', "Einen neuen CSR für {$cert['cn']} erzeugen lassen", "action=newcsr&commonname={$cn}&replace={$oldcert}");
} else {
  addnew('newcsr', 'CSR automatisch erzeugen lassen');
}


output('<h4>Vorhandenes Zertifikat eintragen</h4>
<p>Sie können Ihr eigenes TLS-Zertifikat hinterlegen, das Sie dann für eine oder mehrere Webserver-Konfigurationen verwenden können.</p>
');


$form = '
<h4>Server-Zertifikat:</h4>
<p><textarea name="cert" rows="10" cols="70"></textarea></p>

<h4>privater Schlüssel:</h4>';

if ($oldcert) {
  $form .= '<p>Wenn Ihre Zertifizierungsstelle einen echten <em>renew</em>-Vorgang durchführt und das Zertifikat basierend auf dem alten CSR neu ausgestellt hat, brauchen Sie hier keinen privaten Schlüssel anzugeben.</p>';
}

$form .= '<p><textarea name="key" rows="10" cols="70"></textarea></p>

<p><input type="submit" value="Speichern" /></p>

';

output(html_form('vhosts_certs_new', 'savecert', 'action=new&replace='.$oldcert, $form));

output('<p>Es ist ein <strong>Zertifikat</strong> und ein dazu passender <strong>privater Schlüssel</strong> (ohne Passwort!) nötig. Alle Daten müssen im <strong>PEM-Format</strong> vorliegen, also in etwa die Form</p>
<pre>-----BEGIN CERTIFICATE-----
...
-----END CERTIFICATE-----</pre>
<p>aufweisen.');

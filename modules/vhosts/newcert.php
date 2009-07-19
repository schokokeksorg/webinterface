<?php

include_once('certs.php');
require_role(ROLE_SYSTEMUSER);

$section = 'vhosts_certs';

$title = 'Neues Server-Zertifikat hinzufügen';


output('<h3>Neues Server-Zertifikat hinzufügen</h3>
<p>Sie können Ihr eigenes SSL-Zertifikat hinterlegen, das Sie dann für eine oder mehrere Webserver-Konfigurationen verwenden können.</p>
<p>Sie benötigen dazu mindestens ein <strong>Zertifikat</strong> und einen <strong>privaten Schlüssel</strong> (ohne Passwort!). Alle Daten müssen im <strong>PEM-Format</strong> vorliegen, also in etwa die Form</p>
<pre>-----BEGIN CERTIFICATE-----
...
-----END CERTIFICATE-----</pre>
<p>aufweisen. Sind die genannten Vorausetzungen erfüllt, können Sie Ihre Zertifikats-Daten einfach in untenstehendes Formular eingeben.</p>');


$form = '
<h4>Server-Zertifikat:</h4>
<p><textarea name="cert" rows="10" cols="70"></textarea></p>

<h4>privater Schlüssel:</h4>
<p><textarea name="key" rows="10" cols="70"></textarea></p>

<p><input type="submit" value="Speichern" /></p>

';

output(html_form('vhosts_certs_new', 'savecert', 'action=new', $form));

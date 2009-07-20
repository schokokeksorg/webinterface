<?php

require_once('certs.php');
require_role(ROLE_SYSTEMUSER);


$section = "vhosts_certs";
$title = "Zertifikat zum CSR hinzufügen";

$csr = csr_details($_REQUEST['id']);

output("<h3>Zertifikat zu CSR hinzufügen</h3>
<p>Wenn Ihr CSR von der Zertifizierungsstelle akzeptiert und unterschrieben wurde, erhalten Sie ein SSL-Zertifikat zurück.
Dieses hat in etwa die Form
<pre>-----BEGIN CERTIFICATE-----
...
-----END CERTIFICATE-----</pre>
und kann direkt unten eingetragen werden.</p>

<p>Bitte stellen Sie sicher, dass es sich um das richtige Zertifikat handelt. Der CSR wurde ausgestellt auf den 
Host-/Domainnamen <strong>{$csr['hostname']}</strong>. Nur das dazu passende Zertifikat wird akzeptiert.</p>

<p>Wenn die Überprüfung erfolgreich verläuft, wird der CSR in unserer Datenbank gelöscht, er wird nicht mehr benötigt.
Das Zertifikat und der private Schlüssel werden Ihnen umgehend für Ihre Websites zur Verfügung gestellt.</p>");

$form = '
<h4>Zertifikat:</h4>
<p><textarea name="cert" rows="10" cols="70"></textarea></p>

<p><input type="submit" value="Speichern" /></p>

';

output(html_form('vhosts_certs_new', 'savecert', 'action=new&csr='.$csr['id'], $form));









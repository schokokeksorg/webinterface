<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('certs.php');
require_role(ROLE_SYSTEMUSER);


$section = "vhosts_certs";
title("Zertifikat zum CSR hinzufügen");

$csr = csr_details($_REQUEST['id']);

output("<p>Wenn Ihr CSR von der Zertifizierungsstelle akzeptiert und unterschrieben wurde, erhalten Sie ein Zertifikat zurück.
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

$replace = '';
if ($csr['replace']) {
    $replace = "&replace={$csr['replace']}";
}
output(html_form('vhosts_certs_new', 'savecert', 'action=new&csr='.$csr['id'].$replace, $form));

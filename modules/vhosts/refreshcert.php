<?php

require_once('certs.php');
require_role(ROLE_SYSTEMUSER);


$section = "vhosts_certs";
title("Neue Version eines Zertifikats einspielen");

$cert = cert_details($_REQUEST['id']);

output("<p>Ein bereits vorhandenes Zetifikat können Sie (z.B. wenn es bald abläuft) durch eine neue Version des selben 
Zertifikats ersetzen. Die meisten Zertifizierungsstellen bieten diese Funktion an ohne dass ein neuer CSR erzeugt 
werden muss. Der private Schlüssel wird dabei erhalten und kann unverändert weiter benutzt werden.</p>

<p>Bitte stellen Sie sicher, dass es sich um das richtige Zertifikat handelt. Das bisherige Zertifikat wurde 
ausgestellt als <strong>{$cert['subject']}</strong>. Nur das dazu passende Zertifikat wird akzeptiert.</p>

<p>Möchten Sie das Zertifikat durch ein gänzlich neues Zertifikat mit neuem privaten Schlüssel ersetzen, so 
folgen Sie bitte diesem Link: ".internal_link('newcert', 'Neues Zertifikat als Ersatz für dieses Zertifikat 
hochladen', 'replace='.$cert['id'])."</p>

");

$form = '
<h4>neues Zertifikat:</h4>
<p><textarea name="cert" rows="10" cols="70"></textarea></p>

<p><input type="submit" value="Speichern" /></p>

';

output(html_form('vhosts_certs_refresh', 'savecert', 'action=refresh&id='.$cert['id'], $form));









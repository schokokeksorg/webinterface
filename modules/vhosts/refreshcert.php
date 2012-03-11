<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2012 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

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









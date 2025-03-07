<?php

/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once("certs.php");
require_role(ROLE_SYSTEMUSER);


title("Neues Zertifikat (CSR) erzeugen");
$section = 'vhosts_certs';


output("<p>Mit dieser Funktion können Sie ein neues Zertifikat erzeugen lassen. Dabei wird ein so genannter 
CSR (certifikate signing request) erzeugt. Diesen CSR müssen Sie dann (meistens per Webinterface) an 
Ihre Zertifizierungsstelle übergeben. Von dort erhalten Sie dann das fertige, unterschriebene 
Zertifikat zurück.</p>

<p>Um diese Funktion möglichst einfach zu halten, können Sie damit nicht alle Einstellungen
eines CSR festlegen. Insbesondere die Angabe des Inhabers wurde hier entfernt. CAcert (und andere 
günstige CAs) würde diese Zusatzinformationen sowieso aus dem Zertifikat entfernen. Für eigene 
Einstellungen stehen Ihnen die OpenSSL-Programme in Ihrem Benutzeraccount zur Verfügung.</p>
");

$bitselect = [2048 => 2048, 4096 => 4096];

$form = '<p><label for="commonname">Domain-/Hostname:</label> <input type="text" name="commonname" id="commonname" /> (Mehrere Hostnames ggf. mit Komma trennen.)</p>
<p><label for="bitlength">Bitlänge:</label> ' . html_select('bitlength', $bitselect, 2048) . '</p>
<p><input type="submit" value="Erzeugen" /></p>';

output(html_form('vhosts_csr', 'savecert', 'action=newcsr', $form));

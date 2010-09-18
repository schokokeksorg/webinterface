<?php

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

<p>Bei Eingabe einer Domain (»domain.de«) ohne Subdomain (also nicht »www.domain.de«) wird ein
<strong>Catch-All-Zertifikat</strong> erstellt, das für sämtliche Subdomains genutzt werden kann. Manche kommerziellen
Zertifikats-Anbieter akzeptieren keine solchen Zertifikate in den günstigen Tarifen.</p>
");

$bitselect = array(1024 => 1024, 2048 => 2048, 4096 => 4096);

$form = '<p><label for="commonname">Domain-/Hostname:</label> <input type="text" name="commonname" id="commonname" /></p>
<p><label for="bitlength">Bitlänge:</label> '.html_select('bitlength', $bitselect, 4096).'</p>
<p><input type="submit" value="Erzeugen" /></p>';

output(html_form('vhosts_csr', 'savecert', 'action=newcsr', $form));







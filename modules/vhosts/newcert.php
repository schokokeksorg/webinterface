<?php

include_once('certs.php');
require_role(ROLE_SYSTEMUSER);

$hint = '';
$oldcert = NULL;
if (isset($_REQUEST['replace']))
{
  $cert = cert_details($_REQUEST['replace']);
  $oldcert = $cert['id'];
  $hint = "<p class=\"warning\"><strong>Hinweis:</strong> Dieses Zertifkkat soll als Ersatz für ein bestehendes Zertifikat eingetragen werden. Dabei wird jede Benutzung des alten Zertifikats durch das neue ersetzt. Das alte Zertifikat wird dann umgehend gelöscht.<p>

<p><strong>Daten des alten Zertifikats:</strong></p>
<p><strong>CN:</strong> {$cert['cn']}<br /><strong>Gültigkeit:</strong> {$cert['valid_from']} - {$cert['valid_until']}</p>";

}

$section = 'vhosts_certs';

$title = 'Neues Server-Zertifikat hinzufügen';


output('<h3>Neues Server-Zertifikat hinzufügen</h3>
<p>Sie können Ihr eigenes SSL-Zertifikat hinterlegen, das Sie dann für eine oder mehrere Webserver-Konfigurationen verwenden können.</p>
<p>Sie benötigen dazu mindestens ein <strong>Zertifikat</strong> und einen <strong>privaten Schlüssel</strong> (ohne Passwort!). Alle Daten müssen im <strong>PEM-Format</strong> vorliegen, also in etwa die Form</p>
<pre>-----BEGIN CERTIFICATE-----
...
-----END CERTIFICATE-----</pre>
<p>aufweisen. Sind die genannten Vorausetzungen erfüllt, können Sie Ihre Zertifikats-Daten einfach in untenstehendes Formular eingeben.</p>
'.$hint);


$form = '
<h4>Server-Zertifikat:</h4>
<p><textarea name="cert" rows="10" cols="70"></textarea></p>

<h4>privater Schlüssel:</h4>
<p><textarea name="key" rows="10" cols="70"></textarea></p>

<p><input type="submit" value="Speichern" /></p>

';

output(html_form('vhosts_certs_new', 'savecert', 'action=new&replace='.$oldcert, $form));

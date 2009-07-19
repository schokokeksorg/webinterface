<?php

require_once("certs.php");
require_role(ROLE_SYSTEMUSER);

$title = "SSL-Zertifikate";

output('<h3>SSL-Zertifikate</h3>
<p>Bei schokokeks.org können Sie Ihre eigenen SSL-Zertifikate nutzen. Wir verwenden dafür (wenn nicht anders vereinbart) die <a href="https://wiki.schokokeks.org/SNI">SNI-Technik</a>.</p>
<p>Das Verfahren ist bei uns folgendermaßen implementiert: Sie können hier eines oder mehrere SSL-Zertifikate hochladen, die Sie vorher extern erzeugt haben. Beim Anlegen von Webserver-Konfigurationen können Sie dann eines Ihrer Zertifikate für jede Konfiguration auswählen.</p>

<h4>Ihre bisher vorhandenen Zertifikate</h4>
');

$certs = user_certs();

if (count($certs) > 0)
{
  output("<table><tr><th>Name/Details</th><th>CommonName</th><th>Gültig ab</th><th>Gültig bis</th><th>&#160;</th></tr>");
  foreach ($certs as $c)
  {
    output("<tr><td>{$c['subject']}</td><td>{$c['cn']}</td><td>{$c['valid_from']}</td><td>{$c['valid_until']}</td><td>".internal_link('savecert', '<img src="'.$prefix.'images/delete.png" />', 'action=delete&id='.$c['id'])."</td></tr>");
  } 
  output("</table>");
}
else
{
  output('<p><em>Bisher haben Sie keine Zertifikate eingetragen</em></p>');
}

output('<p>'.internal_link('newcert', 'Neues Zertifikat hinzufügen').'</p>');









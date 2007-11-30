<?php
require_once('inc/debug.php');
require_once('inc/security.php');

require_once('vhosts.php');

$title = "Subdomains";
$error = '';

require_role(ROLE_SYSTEMUSER);

global $prefix;

output("<h3>Subdomains</h3>
<p>Mit dieser Funktion legen Sie fest, welche Domains und Subdomains als Webserver-Ressource verfügbar sein sollen und welches Verzeichnis die Dateien enthalten soll.</p>
<p>Änderungen an Ihren Einstellungen werden im 5-Minuten-Takt auf dem Server übernommen.</p>
");


$vhosts = list_vhosts();

if (count($vhosts) > 0)
{
  output("<table><tr><th>(Sub-)Domain</th><th></th><th>Zusätzliche Alias-Namen</th><th>Lokaler Pfad<sup>*</sup></th><th>PHP</th></tr>");

  foreach ($vhosts as $vhost)
  {
    $fqdn = $vhost['fqdn'];
    output("<tr><td>".internal_link('edit.php', $fqdn, "vhost={$vhost['id']}")."</td><td>".internal_link('save.php', "<img src=\"{$prefix}images/delete.png\" title=\"»{$vhost['fqdn']}« löschen\" alt=\"löschen\" />", 'action=delete&vhost='.$vhost['id'] )."</td><td>");
    $aliases = get_all_aliases($vhost['id']);
    foreach ($aliases as $alias)
    {
      output($alias['fqdn'].'<br />');
    }
    output(internal_link('aliases.php', 'Aliase verwalten', 'vhost='.$vhost['id']));
    output('</td>');
    if ($vhost['docroot_is_default'] == 1)
      output("<td><span style=\"color:#777;\">{$vhost['docroot']}</span></td>");
    else
      output("<td><strong>{$vhost['docroot']}</strong></td>");
    $php = $vhost['php'];
    switch ($php)
    {
      case NULL:
        $php = 'kein PHP';
        break;
      case 'mod_php':
        $php = 'Apache-Modul';
        break;
      case 'fastcgi':
        $php = 'FastCGI';
        break;
    }
    output("<td>{$php}</td>
    </tr>");
  }
  output('</table>');
  output('<p><sup>*</sup>)&#160;schwach geschriebene Pfadangaben bezeichnen die Standardeinstellung. Ist ein Pfad fett dargestellt, so haben Sie einen davon abweichenden Wert eingegeben.</p>');
  output('  <br />');
}
output('<p><a href="edit.php">Neue Subdomain anlegen</a></p>');
output('  <br />');


?>

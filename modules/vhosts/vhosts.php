<?php
require_once('inc/debug.php');
require_once('inc/security.php');

require_once('vhosts.php');

$title = "Webserver VHosts";
$error = '';

require_role(ROLE_SYSTEMUSER);


output("<h3>Webserver (VHosts)</h3>
<p>Mit dieser Funtkion legen Sie fest, welche Domains und Subdomains verfügbar sein sollen und welches Verzeichnis die Dateien enthalten soll.</p>");

$vhosts = list_vhosts();

if (count($vhosts) > 0)
{
  output("<table><tr><th>(Sub-)Domain</th><th>Zusätzliche Alias-Namen</th><th>Lokaler Pfad</th><th>PHP</th></tr>");
  
  foreach ($vhosts as $vhost)
  {
    $fqdn = $vhost['fqdn'];
    if (strstr($vhost['options'], 'aliaswww'))
      $fqdn = 'www.'.$vhost['fqdn'];
    output("<tr><td>".internal_link('edit.php', $fqdn, "vhost={$vhost['id']}")."</td><td>");
    $aliases = get_aliases($vhost['id']);
    if (strstr($vhost['options'], 'aliaswww'))
      output($vhost['fqdn'].'<br />');
    foreach ($aliases as $alias)
    {
      if (strstr($alias['options'], 'aliaswww'))
        output('www.'.$alias['fqdn'].'<br />');
      output($alias['fqdn'].'<br />');
    }
    output('</td>');
    if ($vhost['docroot_is_default'] == 1)
      output("<td>{$vhost['docroot']}</td>");
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
    output("<td>{$php}</td></tr>");
  }
  output('</table><br />');
}



?>

<?php
require_once('inc/debug.php');
require_once('inc/security.php');
require_once('inc/icons.php');

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
  output("<table><tr><th>(Sub-)Domain</th><th></th><th>Zusätzliche Alias-Namen</th><th>Protokoll</th><th>SSL</th><th>PHP</th><th>Lokaler Pfad<sup>*</sup></th></tr>\n");

  $even = True;

  foreach ($vhosts as $vhost)
  {
    $even = ! $even;
    $fqdn = $vhost['fqdn'];
    $class = 'odd';
    if ($even) $class = 'even';
    output("<tr class=\"{$class}\"><td>".internal_link('edit', $fqdn, "vhost={$vhost['id']}", 'title="Einstellungen bearbeiten"')."</td><td>".internal_link('save', icon_delete("»{$vhost['fqdn']}« löschen"), 'action=delete&vhost='.$vhost['id'] )."</td><td>");
    $aliases = get_all_aliases($vhost['id']);
    $tmp = '';
    if (count($aliases) > 0)
    {
      foreach ($aliases as $alias)
      {
        $tmp .= $alias['fqdn'].'<br />';
      }
    } else {
      $tmp = '<em>- keine -</em>';
    }
    output(internal_link('aliases', $tmp, 'vhost='.$vhost['id'], 'title="Aliase verwalten"'));
    output('</td>');
    $logfiles = 'Kein Protokoll';
    if ($vhost['logtype'] == 'default')
      $logfiles = 'Zugriffe';
    elseif ($vhost['logtype'] == 'anonymous')
      $logfiles = 'Zugriffe anonym';
    if ($vhost['errorlog'] == 1)
    {
      if ($vhost['logtype'] == NULL)
        $logfiles = 'Nur Fehler';
      else
        $logfiles .= ' und Fehler';
    }
    output("<td>{$logfiles}</td>");
  
    if ($vhost['ssl'] == 'http')
    {
      output("<td>".icon_disabled('SSL ausgeschaltet')."</td>");
    }
    elseif ($vhost['cert'])
    {
      output("<td><img src=\"{$prefix}images/secure.png\" style=\"height: 16px; width: 16px;\" alt=\"cert\" title=\"SSL mit eigenem Zertifikat\" /></td>");
    }
    else
    {
      output("<td>".icon_enabled('SSL eingeschaltet')."</td>");
    }

    if ($vhost['is_webapp'] == 1) {
      output('<td colspan="2"><em><strong>Sonderanwendung:</strong> Vorinstallierte Webanwendung</em></td>');
    }
    elseif ($vhost['is_dav'] == 1) {
      output('<td colspan="2"><em><strong>Sonderanwendung:</strong> WebDAV</em></td>');
    }
    elseif ($vhost['is_svn'] == 1) {
      output('<td colspan="2"><em><strong>Sonderanwendung:</strong> Subversion-Server</em></td>');
    }
    else {
      $php = $vhost['php'];
      switch ($php)
      {
        case NULL:
          $php = icon_disabled('PHP ausgeschaltet');
          break;
        case 'mod_php':
          $php = icon_warning('[mod_php] Veraltet, bitte umstellen!').' Apache-Modul';
          break;
        case 'fastcgi':
          $php = icon_enabled('PHP eingeschaltet (PHP 5.2)');
          break;
        case 'php53':
          $php = icon_enabled('PHP eingeschaltet (PHP 5.3)');
          break;
      }
      output("<td>{$php}</td>");
      if ($vhost['docroot_is_default'] == 1)
        output("<td><span style=\"color:#777;\">{$vhost['docroot']}</span></td>");
      else
        output("<td><strong>{$vhost['docroot']}</strong></td>");
    }
    output("</tr>\n");
  }
  output('</table>');
  output('<p style="font-size: 90%;"><sup>*</sup>)&#160;schwach geschriebene Pfadangaben bezeichnen die Standardeinstellung. Ist ein Pfad fett dargestellt, so haben Sie einen davon abweichenden Wert eingegeben.</p>');
}
else // keine VHosts vorhanden
{
  output("<p><strong><em>Bisher haben Sie keine Domain bzw. Subdomain eingerichtet.</em></strong></p>");
}

addnew('edit', 'Neue Domain bzw. Subdomain einrichten');

addnew('../webapps/install', 'Neue Domain bzw. Subdomain mit vorinstallierter Web-Anwendung einrichten');

?>

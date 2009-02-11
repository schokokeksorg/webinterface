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
  output("<table><tr><th>(Sub-)Domain</th><th></th><th>Zusätzliche Alias-Namen</th><th>Protokoll</th><th>PHP</th><th>Lokaler Pfad<sup>*</sup></th></tr>\n");

  $even = True;

  foreach ($vhosts as $vhost)
  {
    $even = ! $even;
    $fqdn = $vhost['fqdn'];
    $class = 'odd';
    if ($even) $class = 'even';
    output("<tr class=\"{$class}\"><td>".internal_link('edit', $fqdn, "vhost={$vhost['id']}", 'title="Einstellungen bearbeiten"')."</td><td>".internal_link('save', "<img src=\"{$prefix}images/delete.png\" title=\"»{$vhost['fqdn']}« löschen\" alt=\"löschen\" />", 'action=delete&vhost='.$vhost['id'] )."</td><td>");
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
          $php = "<img src=\"{$prefix}images/error.png\" alt=\"aus\" title=\"PHP ausgeschaltet\" />";
          break;
        case 'mod_php':
          $php = "<img src=\"{$prefix}images/warning.png\" alt=\"mod_php\" title=\"Veraltet: Bitte umstellen\" /> Apache-Modul";
          break;
        case 'fastcgi':
          $php = "<img src=\"{$prefix}images/ok.png\" alt=\"ein\" title=\"PHP eingeschaltet\" />";
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
  output('<p><sup>*</sup>)&#160;schwach geschriebene Pfadangaben bezeichnen die Standardeinstellung. Ist ein Pfad fett dargestellt, so haben Sie einen davon abweichenden Wert eingegeben.</p>');
  output('  <br />');
}
output('<p>'.internal_link('edit', 'Neue Domain bzw. Subdomain einrichten').'</p>');
output('  <br />');


?>

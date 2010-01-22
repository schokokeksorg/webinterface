<?php

require_once("inc/icons.php");

require_once("vhosts.php");

require_role(ROLE_SYSTEMUSER);



if (isset($_REQUEST['vhost']))
{
  $v = get_vhost_details($_REQUEST['vhost']);

  if (isset($_REQUEST['public']))
  {
    $v['stats'] = ($_REQUEST['public'] == 1) ? 'public' : 'private';
  }
  if (isset($_REQUEST['action']))
  {
    if ($_REQUEST['action'] == 'delete')
      $v['stats'] = NULL;
    elseif ($_REQUEST['action'] == 'new')
      check_form_token('stats_new');
  }
  save_vhost($v);
  redirect('stats');
}
else
{

$all_vhosts = list_vhosts();
$stats_vhosts = array();

foreach ($all_vhosts AS $v)
  if ($v['stats'])
    $stats_vhosts[] = $v;


$title = "Zugriffs-Statistiken";

output('<h3>Zugriffs-Statistiken</h3>
<p>Um die Reichweite und das Publikum Ihrer Internet-Seiten besser einschätzen zu können, besteht die Möglichkeit aus den ggf. vorhandenen Webserver-Logfiles grafisch aufbereitete Statistiken erstellen zu lassen.</p>

<h3>Statistiken für Ihre Seiten</h3>
');

if (count($stats_vhosts) > 0)
{
  output('
  <table><tr><th>Für Website</th><th>Öffentlich abrufbar?</th><th>Operationen</th></tr>
  ');

  foreach ($stats_vhosts AS $v)
  {
    output("<tr>");
    output("<td>".internal_link('showstats', $v['fqdn'], "vhost={$v['id']}")."</td>");

    if ($v['stats'] == 'public')
      output("<td><a href=\"http://stats.schokokeks.org/{$v['fqdn']}\">".icon_enabled("Diese Statistiken können von jedermann aufgerufen werden. Klicken Sie hier um die öffentliche Version zu sehen.")."</a></td>");
    else
      output("<td>".icon_disabled("Diese Statistiken können nur hier im Webinterface betrachtet werden.")."</td>");
    
    output("<td>");
    if ($v['stats'] == 'public')
      output(internal_link("", other_icon("lock.png", "Statistiken nicht mehr öffentlich anzeigen"), "vhost={$v['id']}&public=0"));
    else
      output(internal_link("", other_icon("world.png", "Statistiken veröffentlichen"), "vhost={$v['id']}&public=1"));
    output(" &#160; ".internal_link("", icon_delete("Diese Statistiken löschen"), "vhost={$v['id']}&action=delete")."</td>");
    output("</tr>");
  
  }
  output('</table');
}
else
  output('<em>Für Ihre Seiten werden bisher keine Statistiken erzeugt</em>');


output("<h3>Weitere Statistiken</h3>");

$sel = array();
foreach ($all_vhosts AS $v)
{
  if ($v['logtype'])
  {
    $found = False;
    foreach ($stats_vhosts AS $s)
      if ($s['id'] == $v['id'])
        $found = True;
    if (! $found)
      $sel[$v['id']] = $v['fqdn'];
  }
}


output(html_form('stats_new', '', 'action=new', "<p>".html_select("vhost", $sel).'<br/>
<input type="radio" name="public" id="public_0" value="0" checked="checked" /><label for="public_0"> Statistiken hier im Webinterface anzeigen</label><br />
<input type="radio" name="public" id="public_1" value="1" /><label for="public_1"> Statistiken unter stats.schokokeks.org veröffentlichen (Ohne Passwortschutz)</label><br />
<input type="submit" value="Neue Statistiken erzeugen" /></p>
'));


output('

<p><strong>Hinweis:</strong> Die Statistiken werden mindestens täglich erzeugt. Bis zum ersten Durchlauf nach der Aktivierung der Statistik wird der obige Link eine Fehlermeldung erzeugen. Bitte warten Sie mindestens einen Tag ab bevor Sie die Statistik zum ersten Mal aufrufen.</p>
');

}

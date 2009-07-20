<?php

require_once('inc/base.php');
require_once('mailman.php');

require_role(ROLE_SYSTEMUSER);

$title = 'Mailinglisten';

output('<h3>Mailinglisten</h3>
<p>Mit <a href="http://www.mailman.org">Mailman</a> bieten wir Ihnen eine umfangreiche Lösung für E-Mail-Verteilerlisten an. <a href="https://wiki.schokokeks.org/E-Mail/Mailinglisten">In unserem Wiki</a> sind die Möglichkeiten einer Mailingliste detaillierter beschrieben.</p>
<p>Auf dieser Seite können Sie Ihre Mailinglisten verwalten.</p>
');

$lists = get_lists();

if (! empty($lists))
{
  output("<table>\n<tr><th>Listenname</th><th>Verwalter</th><th>Status</th><th>&nbsp;</th></tr>\n");
  foreach ($lists AS $list)
  {
    $style = '';
    $status = 'In Betrieb';
    if ($list['status'] == 'delete')
    {
      $style = ' style="text-decoration: line-through;" ';
      $status = 'Wird gelöscht';
    }
    elseif ($list['status'] == 'pending')
    {
      $style = ' style="text-decoration: underline;" ';
      $status = 'Wird angelegt';
    }
    elseif ($list['status'] == 'failure')
    {
      $style = ' style="font-style: italic;" ';
      $status = 'Fehler bei der Erstellung';
    }

    output("<tr><td{$style}><strong>{$list['listname']}</strong>@{$list['fqdn']}</td><td{$style}>{$list['admin']}</td><td>{$status}</td>");
    if ($list['status'] == 'running')
      output("<td>".internal_link('save', "<img src=\"{$prefix}images/delete.png\" />", "action=delete&id={$list['id']}")."</tr>\n");
    else
      output("<td>&#160;</td></tr>\n");
  }
  output("</table>");
}
else
{
  // keine Listen
  output('<p><em>Sie betreiben bisher keine Mailinglisten.</em></p>');
}

addnew('newlist', 'Neue Mailingliste anlegen');
output("
<p><strong>Hinweis:</strong> Sie können im Webinterface von Mailman einen abweichenden oder auch mehrere Verwalter eintragen. Die Information auf dieser Seite wird dann nicht automatisch geändert sondern bezeichnet den Verwalter, den Sie beim Anlegen der Liste benannt haben.</p>\n");


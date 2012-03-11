<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2012 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/base.php');
require_once('mailman.php');

require_role(ROLE_SYSTEMUSER);

title('Mailinglisten');

output('<p>Mit <a href="http://www.gnu.org/software/mailman/index.html">Mailman</a> bieten wir Ihnen eine umfangreiche Lösung für E-Mail-Verteilerlisten an.</p>
<p>Auf dieser Seite können Sie Ihre Mailinglisten verwalten.</p>
');

$lists = get_lists();

if (! empty($lists))
{
  output("<table>\n<tr><th>Listenname</th><th>Verwalter<sup>1</sup></th><th>Status</th><th>Archivgröße<sup>2</sup></th><th>&nbsp;</th></tr>\n");
  foreach ($lists AS $list)
  {
    $size = $list['archivesize'];
    $sizestr = $size.' Bytes';
    if (! $size) {
      $sizestr = '<em>Kein Archiv</em>';
    }
    else {
      $sizestr = sprintf('%.2f', $size/(1024*1024)).' MB';
    }


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

    output("<tr><td{$style}><strong>{$list['listname']}</strong>@{$list['fqdn']}</td><td{$style}>{$list['admin']}</td><td>{$status}</td><td style=\"text-align: right;\">{$sizestr}</td>");
    if ($list['status'] == 'running')
      output("<td>".internal_link('save', "<img src=\"{$prefix}images/delete.png\" />", "action=delete&id={$list['id']}")."</td></tr>\n");
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
<p><strong>Hinweise:</strong><br />
<sup>1</sup>) Sie können später im Webinterface von Mailman einen abweichenden oder auch mehrere Verwalter eintragen. Die Information auf dieser Seite wird dann nicht automatisch geändert sondern bezeichnet den Verwalter, den Sie beim Anlegen der Liste benannt haben.<br />
<sup>2</sup>) Die Größe der Archive wird in regelmäßigen Abständen eingelesen. Der hier angezeigte Wert ist möglicherweise nicht mehr aktuell.</p>\n");


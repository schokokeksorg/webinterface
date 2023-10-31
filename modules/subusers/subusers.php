<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/icons.php');

require_role(ROLE_SYSTEMUSER);

include("subuser.php");

title("Zusätzliche Admin-Zugänge");

output('<p>Sie können für dieses Admin-Interface zusätzliche Accounts anlegen, die dann nur Zugriff auf einzelne Module haben. So ist es z.B. möglich, einen Mail-Admin und einen Webserver-Admin festzulegen.</p><p><strong>Wichtig:</strong> Diese zusätzlichen Zugänge gelten ausschließlich für dieses Web-Interface, nicht für die Anmeldung am Server.</p>');

$subusers = list_subusers();

$available_modules = available_modules();
DEBUG($available_modules);

if ($subusers) {
    output('<h4>Momentan vorhandene zusätzliche Admin-Zugänge</h4>');
    foreach ($subusers as $subuser) {
        output('<div><p><strong>' . $subuser['username'] . '</strong> ' . internal_link('delete.php', icon_delete('Löschen'), 'subuser=' . $subuser['id']) . ' ' . internal_link('edit.php', icon_edit('Bearbeiten'), 'subuser=' . $subuser['id']) . '</p>');
        output('<ul>');
        foreach ($subuser['modules'] as $mod) {
            if (isset($available_modules[$mod])) {
                output('<li>' . $available_modules[$mod] . '</li>');
            }
        }
        output('</ul></div>');
    }
} else {
    output('<p><em>Sie haben bisher keine zusätzlichen Admin-Zugänge</em></p>');
}

addnew('edit.php', 'Neuen zusätzlichen Admin-Zugang anlegen...');

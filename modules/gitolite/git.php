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

require_once('inc/icons.php');

include('git.php');
require_role(ROLE_SYSTEMUSER);

$section = 'git_git';
title("GIT-Zugänge");

output("<p>Das verteilte Versionskontrollsystem <a href=\"http://www.git-scm.org\">GIT</a> ist ein populäres Werkzeug um Programmcode zu verwalten. Mit dieser Oberfläche können Sie GIT-repositories erstellen und den Zugriff für mehrere Benutzer festlegen.</p>");
output("<p>Wir verwenden das beliebte System »gitolite« um diese Funktionalität anzubieten. Gitolite erlaubt bei Bedarf weitaus feingliedrigere Kontrolle als dieses Webinterface. Fragen Sie bitte den Support, wenn Sie Interesse daran haben zusätzliche Berechtigungen einzurichten.</p>");

$repos = list_repos();
$users = list_users();
$foreign_users = list_foreign_users();

if (count($repos) == 0) {
  output("<p><em>bisher haben Sie keine GIT-Repositories</em></p>");
} else {
  output("<h3>Ihre GIT-Repositories</h3>"); 
}

foreach ($repos as $repo => $settings) {
  $description = $settings['description'] ? '<br /><em>"'.$settings['description'].'"</em>' : '';
  $url = get_git_url($repo);
  $public = isset($settings['users']['gitweb']) && $settings['users']['gitweb'] == 'R';
  $public_string = '';
  if ($public) {
    $public_url = 'http://'.config('gitserver').'/'.$repo.'.git';
    $git_url = 'git://'.config('gitserver').'/'.$repo.'.git';
    $public_string = '<br />(Öffentlicher Lesezugriff über <a href="'.$public_url.'">'.$public_url.'</a> und '.$git_url.')';
  }
  output("<div><p><strong>{$repo}</strong> ".internal_link('edit', icon_edit('Zugriffsrechte bearbeiten'), 'repo='.$repo)." ".internal_link('delete', icon_delete('Repository löschen'), 'repo='.$repo)."{$description}<br />push-Adresse: {$url} {$public_string}</p><ul>");
  foreach ($settings['users'] as $user => $rights) {
    if ($user == 'gitweb' || $user == 'daemon') {
      continue;
    }
    $grant = '';
    switch ($rights) {
      case 'R': $grant = 'Lesezugriff';
                break;
      case 'RW': $grant = 'Lese- und Schreibzugriff';
                break;
      case 'RW+': $grant = 'erweiterter Zugriff (inkl. "rewind")';
                break;
    }    
    output("<li>{$user}: {$grant}</li>");
  }
  output("</ul></div>");
}

if (count($users) > 0) {
  addnew('edit', 'Neues GIT-Repository anlegen');
} else {
  output('<p><em>Bitte legen Sie zunächst mindestens einen SSH-Key an.</em></p>');
}



if (count($users) == 0) {
  output('<p><em>Es sind bisher keine SSH-Keys eingerichtet.</em></p>');
} else {
  output('<h3>Ihre aktuell hinterlegten SSH-Keys</h3>');
}

foreach ($users as $handle) {
  output('<p><strong>'.$handle.'</strong> '.internal_link('newkey', icon_edit('Hinterlegten SSH-Key ändern'), 'handle='.$handle)." ".internal_link('delete', icon_delete('SSH-Key löschen'), 'handle='.$handle)."</p>");
}

addnew('newkey', 'Neuen SSH-Key eintragen');


if (count($foreign_users) == 0) {
  output('<p><em>Es sind bisher keine GIT-Benutzer anderer Kunden eingetragen.</em></p>');
} else {
  output('<h3>GIT-Benutzer anderer Kunden</h3>');
}

foreach ($foreign_users as $handle) {
  output('<p><strong>'.$handle.'</strong> '.internal_link('delete', icon_delete('Benutzer aus diesem Kundenaccount entfernen'), 'foreignhandle='.$handle)."</p>");
}

addnew('newforeignuser', 'GIT-Benutzer anderer Kunden freischalten');


output('<p style="font-size: 90%;padding-top: 0.5em; border-top: 1px solid black;">Hinweis: Die hier gezeigten Berechtigungen können unter Umständen nicht aktuell sein. Bei Fehlfunktionen sollten Sie '.internal_link('refresh', 'die Berechtigungen neu einlesen lassen').'</p>');

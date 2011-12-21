<?php
require_once('inc/icons.php');

include('git.php');
require_role(ROLE_SYSTEMUSER);

title("GIT-Zugänge");

output("<p>Das verteilte Versionskontrollsystem <a href=\"http://www.git-scm.org\">GIT</a> ist ein populäres Werkzeug um Programmcode zu verwalten. Mit dieser Oberfläche können Sie GIT-repositories erstellen und den Zugriff für mehrere Benutzer festlegen.</p>");
output("<p>Wir verwenden das beliebte System »gitolite« um diese Funktionalität anzubieten. Gitolite erlaubt bei Bedarf weitaus feingliedrigere Kontrolle als dieser Webinterface. Fragen Sie bitte den Support, wenn Sie Interesse daran haben zusätzliche Berechtigungen einzurichten.</p>");

$repos = list_repos();
$users = list_users();

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
    $public_url = 'http://git.schokokeks.org/'.$repo.'.git';
    $public_string = '(Öffentlich abrufbar über <a href="'.$public_url.'">'.$public_url.'</a>)';
  }
  output("<div><p><strong>{$repo}</strong> ".internal_link('edit', icon_edit('Zugriffsrechte bearbeiten'), 'repo='.$repo)." ".internal_link('delete', icon_delete('Repository löschen'), 'repo='.$repo)."{$description}<br />push-Adresse: {$url} {$public_string}</p><ul>");
  foreach ($settings['users'] as $user => $rights) {
    if ($user == 'gitweb') {
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

output('<p style="font-size: 90%;padding-top: 0.5em; border-top: 1px solid black;">Hinweis: Die hier gezeigten Berechtigungen können unter Umständen nicht aktuell sein. Bei Bearf können Sie '.internal_link('refresh', 'die Berechtigungen neu einlesen lassen').'</p>');

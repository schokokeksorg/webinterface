<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2018 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('session/start.php');
require_once('webapp-installer.php');

require_role(ROLE_SYSTEMUSER);

$section = 'webapps_freewvs';
$directory = $_GET['dir'];

if (! in_homedir($directory)) {
    system_failure('Pfad nicht im Homedir oder ungültige Zeichen im Pfad');
}

$app = $_GET['app'];
verify_input_identifier($app);


$sure = user_is_sure();
if ($sure === null) {
    are_you_sure("dir={$directory}&app=".filter_output_html($app), "Möchten Sie ein Update der Anwendung »".filter_output_html($app)."« im Verzeichnis »{$directory}« automatisch durchführen lassen?");
} elseif ($sure === true) {
    request_update($app, $directory, get_url_for_dir($directory));
    if (! $debugmode) {
        header("Location: waitforupdate");
    }
} elseif ($sure === false) {
    if (! $debugmode) {
        header("Location: freewvs");
    }
}

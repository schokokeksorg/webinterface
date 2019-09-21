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

require_once('inc/base.php');
require_once('inc/security.php');

require_once('session/start.php');
require_once('su.php');

require_role(ROLE_SYSADMIN);



if (isset($_GET['do'])) {
    if ($_SESSION['su_ajax_timestamp'] < time() - 30) {
        system_failure("Die su-Auswahl ist schon abgelaufen!");
    }
    $type = $_GET['do'][0];
    $id = (int) substr($_GET['do'], 1);
    su($type, $id);
}

$search = null;
if (isset($_POST['query'])) {
    check_form_token('su_su');
    $id = $_POST['query'];
    if (! su(null, $id)) {
        $search = $_POST['query'];
    }
}

title("Benutzer wechseln");

output('<p>Hiermit können Sie (als Admin) das Webinterface mit den Rechten eines beliebigen anderen Benutzers benutzen.</p>
');

require_once('inc/jquery.php');
// lädt die JS-Datei mit gleichem basename
javascript();

output(html_form('su_su', '', '', '<p><label for="query"><strong>Suchtext:</strong></label> <input type="text" name="query" id="query" /> <input type="submit" value="Suchen" /></p>
'));

if ($search) {
    $allentries = build_results($search);
    foreach ($allentries as $entry) {
        output("  <p><a href=\"?do=".filter_output_html($entry['id'])."\">".filter_output_html($entry['value'])."</a></p>");
    }
}

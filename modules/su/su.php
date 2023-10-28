<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

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
    if (!su(null, $id)) {
        $search = $_POST['query'];
    }
}

title("Benutzer wechseln");

output('<p>Hiermit können Sie (als Admin) das Webinterface mit den Rechten eines beliebigen anderen Benutzers benutzen.</p>
');

require_once('inc/javascript.php');
// lädt die JS-Datei mit gleichem basename
javascript();

output(html_form('su_su', '', '', '<label for="query"><strong>Suchtext:</strong></label> <input type="text" name="query" list="suggestions" id="query" autocomplete="off" /> 
<datalist id="suggestions">
</datalist>
<input type="submit" value="Suchen" />
'));

if ($search) {
    $allentries = build_results($search);
    foreach ($allentries as $entry) {
        output("  <p><a href=\"?do=".filter_output_html($entry['id'])."\">".filter_output_html($entry['value'])."</a></p>");
    }
}

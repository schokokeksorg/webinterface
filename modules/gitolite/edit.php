<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_role(ROLE_SYSTEMUSER);

include("git.php");

$section = 'git_git';

$repos = list_repos();
$users = list_users();
$foreign_users = list_foreign_users();

$action = '';
$form = '';

html_header("<script type=\"text/javascript\">
  function showDescription( ) {
    var do_it = (document.getElementById('gitweb').checked == false);
    var inputfield = document.getElementById('description');
    inputfield.disabled = do_it;
    }
</script>
");

if (isset($_GET['repo']) && isset($repos[$_GET['repo']])) {
    $action = 'editrepo';
    title("Zugriff auf GIT-Repository ändern");
    output("<p>Legen Sie hier fest, welche Berechtigungen für welche SSH-Keys gelten sollen.</p>");
    $form .= '<table><tr><td>Name des Repository</td><td><input type="hidden" name="repo" value="' . filter_output_html($_GET['repo']) . '" />' . filter_output_html($_GET['repo']) . '</td></tr>';
} else {
    $action = 'newrepo';
    title("Neues GIT-Repository anlegen");
    output("<p>Geben Sie einen Namen für das neue Repository an und legen Sie fest, welche Berechtigungen für welche SSH-Keys gelten sollen.</p>");
    $form .= '<table><tr><td><label for="repo">Name des Repository</label></td><td><input type="text" id="repo" name="repo" /></td></tr>';
}

$form .= '<tr><td>Berechtigungen</td><td>';
foreach ($users as $user) {
    $r = $rw = $rwplus = '';
    if (isset($_GET['repo']) && isset($repos[$_GET['repo']])) {
        $permissions = $repos[$_GET['repo']]['users'];
        if (isset($permissions[$user])) {
            switch ($permissions[$user]) {
                case 'RW+': $rwplus = ' selected="selected"';
                    break;
                case 'RW': $rw = ' selected="selected"';
                    break;
                case 'R': $r = ' selected="selected"';
                    break;
            }
        }
    }
    $form .= '<p>' . $user . ': <select name="' . $user . '"><option value="-">Zugriff verweigern</option><option value="r"' . $r . '>Lesezugriff erlauben</option><option value="rw"' . $rw . '>Lese- und Schreibzugriff</option><option value="rwplus"' . $rwplus . '>erweiterter Lese- und Schreibzugriff (inkl. &quot;rewind&quot;)</option></select></p>';
}
foreach ($foreign_users as $user) {
    $r = $rw = $rwplus = '';
    if (isset($_GET['repo']) && isset($repos[$_GET['repo']])) {
        $permissions = $repos[$_GET['repo']]['users'];
        if (isset($permissions[$user])) {
            switch ($permissions[$user]) {
                case 'RW+': $rwplus = ' selected="selected"';
                    break;
                case 'RW': $rw = ' selected="selected"';
                    break;
                case 'R': $r = ' selected="selected"';
                    break;
            }
        }
    }
    $form .= '<p>' . $user . ': <select name="' . $user . '"><option value="-">Zugriff verweigern</option><option value="r"' . $r . '>Lesezugriff erlauben</option><option value="rw"' . $rw . '>Lese- und Schreibzugriff</option><option value="rwplus"' . $rwplus . '>erweiterter Lese- und Schreibzugriff (inkl. &quot;rewind&quot;)</option></select></p>';
}
$checked = (isset($_GET['repo']) && isset($repos[$_GET['repo']]) && isset($repos[$_GET['repo']]['users']['gitweb']) && $repos[$_GET['repo']]['users']['gitweb'] == 'R') ? ' checked="checked"' : '';
$description = (isset($_GET['repo']) && isset($repos[$_GET['repo']])) ? $repos[$_GET['repo']]['description'] : '';
$disabled = $checked ? '' : ' disabled="disabled"';
$form .= '<p><input type="checkbox" name="gitweb" id="gitweb" value="r"' . $checked . ' onclick="showDescription()" /> <label for="gitweb">Öffentlicher Lesezugriff via gitweb</label><br />
<label for="description">Beschreibung des Repository:</label> <input type="text" name="description" id="description" value="' . filter_output_html($description) . '"' . $disabled . ' /></p>';
$form .= '</td></tr></table>';
$form .= '<p><input type="submit" value="Speichern" /></p>';

output(html_form('git_edit', 'save', 'action=' . $action, $form));

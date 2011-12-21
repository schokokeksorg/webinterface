<?php
require_role(ROLE_SYSTEMUSER);

include("git.php");

$section = 'git_git';

$repos = list_repos();

$users = list_users();

$action = '';
$form = '';

if (isset($_GET['repo']) && isset($repos[$_GET['repo']])) {
  $action = 'editrepo';
  title("Zugriff auf GIT-Repository ändern");
  output("<p>Legen Sie hier fest, welche Berechtigungen für welche SSH-Keys gelten sollen.</p>");
  $form .= '<table><tr><td>Name des Repository</td><td><input type="hidden" name="repo" value="'.filter_input_general($_GET['repo']).'" />'.filter_input_general($_GET['repo']).'</td></tr>';
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
    $repo = $repos[$_GET['repo']];
    if (isset($repo[$user])) {
      switch ($repo[$user]) {
        case 'RW+': $rwplus = ' selected="selected"';
                    break;
        case 'RW': $rw = ' selected="selected"';
                   break;
        case 'R': $r = ' selected="selected"';
                  break;
      }
    }
  }
  $form .= $user.': <select name="'.$user.'"><option value="-">Zugriff verweigern</option><option value="r"'.$r.'>Lesezugriff erlauben</option><option value="rw"'.$rw.'>Lese- und Schreibzugriff</option><option value="rwplus"'.$rwplus.'>erweiterter Lese- und Schreibzugriff (inkl. &quot;rewind&quot;)</option></select><br />';
}
$form .= '</td></tr></table>';
$form .= '<p><input type="submit" value="Speichern" /></p>';

output(html_form('git_edit', 'save', 'action='.$action, $form));


<?php
require_role(ROLE_SYSTEMUSER);

include('git.php');

$section = 'git_git';

$handle = '';
if (isset($_GET['handle'])) {
  $handle = filter_input_general($_GET['handle']);
}

$action = '';
$form = '';

$pubkey = '';

if ($handle) {
  $action = 'newkey';
  title('Neuen SSH-Key für GIT-Benutzer');
  output('<p>Legen Sie hier einen neuen SSH-Key für einen bestehenden Benutzer fest.</p>');
  $pubkey = get_pubkey($handle);
} else {
  $action = 'newuser';
  title('Neuer GIT-Benutzer');
  output('<p>Tragen Sie hier einen eindeutigen Namen für den neuen Benutzer fest und hinterlegen Sie einen SSH-Public-Key.</p>');
}

$userprefix = $_SESSION['userinfo']['username'].'-';

$form .= '<table><tr><td><label for="handle" />Name des Benutzers:</label></td>';
if ($handle) {
  $form .= '<td><input type="hidden" name="handle" value="'.str_replace($userprefix, '', $handle).'" /><strong>'.$handle.'</strong></td></tr>';
} else {
  $form .= '<td>'.$userprefix.'<input type="text" id="handle" name="handle" value="'.$handle.'" /></td></tr>';
}
$form .= '<tr><td><label for="pubkey">SSH-Public-Key:</label></td><td><textarea name="pubkey" id="pubkey" cols="70" rows="10">'.$pubkey.'</textarea></td></tr>
  </table>
  <p><input type="submit" value="Speichern" /></p>
  ';


output(html_form('git_newkey', 'save', "action={$action}", $form));

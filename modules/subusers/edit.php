<?php
require_role(ROLE_SYSTEMUSER);
include('subuser.php');

$section = 'subusers_subusers';

if (isset($_GET['subuser'])) {
  $list = list_subusers();
  foreach ($list as $x) {
    if ($x['id'] == $_GET['subuser']) {
      $subuser = $x;
    }
  }
  if (!isset($subuser)) {
    system_failure('Der Account den Sie bearbeiten möchten wurde nicht gefunden!');
  }
  title("Zusätzlichen Admin-Zugang bearbeiten");
  $pwnotice = ' <em>(Wenn Sie hier nichts eingeben, wird das alte Passwort beibehalten)</em>';
} else {
  $subuser = empty_subuser();
  title("Zusätzlichen Admin-Zugang erstellen");
  $pwnotice = '';
}

// Username davor entfernen
$subuser['username'] = str_replace($_SESSION['userinfo']['username'].'_', '', $subuser['username']);

output('Ein zusätzlicher Admin-Zugang darf die hier bestimmten Module dieses Webinterfaces mit den selben Möglichkeiten wie Sie selbst benutzen. Erlauben sie den Zugriff nur vertrauenswürdigen Dritten!');


$form = '<table>
<tr><td><strong><label for="username">Benutzername:</label></td><td>'.$_SESSION['userinfo']['username'].'_<input type="text" name="username" id="username" value="'.$subuser['username'].'" /></td></tr>
<tr><td><strong><label for="password">Passwort:</label></td><td><input type="password" name="password" id="password" value="" />'.$pwnotice.'</td></tr>
<tr><td style="vertical-align: top;">Berechtigungen:</td><td>';
foreach ($modinfo as $key => $desc) {
  $checked = in_array($key, $subuser['modules']) ? 'checked="checked "' : '';
  $form .= '<input type="checkbox" name="modules[]" id="'.$key.'" value="'.$key.'" '.$checked.'/> <label for="'.$key.'">'.$desc.'</label><br />';
}
$form .= '</td></tr>
<tr><td colspan="2"><input type="submit" value="Speichern" /></td></tr>

</table>';

output(html_form('subusers_edit', 'save', 'id='.$subuser['id'], $form));



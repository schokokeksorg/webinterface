<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2013 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

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

output('<p>Ein zusätzlicher Admin-Zugang darf die hier bestimmten Module dieses Webinterfaces mit den selben Möglichkeiten wie Sie selbst benutzen. Erlauben sie den Zugriff nur vertrauenswürdigen Dritten!</p>');


$form = '<table>
<tr><td><strong><label for="username">Benutzername:</label></td><td>'.$_SESSION['userinfo']['username'].'_<input type="text" name="username" id="username" value="'.$subuser['username'].'" /></td></tr>
<tr><td><strong><label for="password">Passwort:</label></td><td><input type="password" name="password" id="password" value="" />'.$pwnotice.'</td></tr>
<tr><td style="vertical-align: top;">Berechtigungen:</td><td>';
$modinfo = available_modules();
foreach ($modinfo as $key => $desc) {
  $checked = in_array($key, $subuser['modules']) ? 'checked="checked "' : '';
  $form .= '<input type="checkbox" name="modules[]" id="'.$key.'" value="'.$key.'" '.$checked.'/> <label for="'.$key.'">'.$desc.'</label><br />';
}
$form .= '<br /><em>(Nicht alle Berechtigungen haben alleinstehend eine Wirkung. Eventuell müssen Sie mehrere Berechtigungen erlauben um einen Effekt zu erhalten.)</em></td></tr>
<tr><td colspan="2"><input type="submit" value="Speichern" /></td></tr>

</table>';

output(html_form('subusers_edit', 'save', 'id='.$subuser['id'], $form));



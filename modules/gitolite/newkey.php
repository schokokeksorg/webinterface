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

include('git.php');

$section = 'git_git';

$handle = '';
if (isset($_GET['handle'])) {
    $handle = verify_input_identifier($_GET['handle']);
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
    output('<p>Tragen Sie hier einen eindeutigen Namen für den neuen Benutzer fest und hinterlegen Sie einen SSH-Public-Key.</p><p><strong>Hinweis:</strong> Es wird nicht funktionieren, mehrere GIT-Zugänge mit dem selben Public-Key einzurichten. Soll ein Entwickler auf GIT-Repositories mehrerer unserer Kunden zugreifen, dann darf der Benutzer nur einmal angelegt werden und muss bei den übrigen Kunden als "GIT-Benutzer eines anderern Kunden" freigeschaltet werden.</p>');
}

$userprefix = $_SESSION['userinfo']['username'].'-';

$form .= '<table><tr><td><label for="handle" />Name des Benutzers:</label></td>';
if ($handle) {
    $form .= '<td><input type="hidden" name="handle" value="'.filter_output_html(str_replace($userprefix, '', $handle)).'" /><strong>'.filter_output_html($handle).'</strong></td></tr>';
} else {
    $form .= '<td>'.$userprefix.'<input type="text" id="handle" name="handle" value="'.$handle.'" /></td></tr>';
}
$form .= '<tr><td><label for="pubkey">SSH-Public-Key:</label></td><td><textarea name="pubkey" id="pubkey" cols="70" rows="10">'.filter_output_html($pubkey).'</textarea></td></tr>
  </table>
  <p><input type="submit" value="Speichern" /></p>
  ';


output(html_form('git_newkey', 'save', "action={$action}", $form));

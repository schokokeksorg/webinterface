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

$form = '';

title('GIT-Benutzer eines anderen Kunden hinzufügen');
output('<p>Um anderen GIT-Benutzern, die einen anderen Kundenaccount verwenden, ebenfalls Zugriff auf Ihre GIT-Repositories einzuräumen, können Sie hier GIT-Benutzer anderer Kunden freischalten.</p>');

$form .= '<table>
    <tr><td><label for="handle" />Name des GIT-Benutzers:</label></td><td><input type="text" id="handle" name="handle" value="" /></td></tr>
  </table>
  <p><input type="submit" value="Speichern" /></p>
  ';


output(html_form('git_newforeignuser', 'save', 'action=newforeignuser', $form));

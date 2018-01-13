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

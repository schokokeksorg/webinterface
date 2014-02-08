<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2014 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_role(ROLE_SYSTEMUSER);
include("subuser.php");

$section = 'subusers_subusers';

if (!isset($_POST['username']) || $_POST['username'] == '') {
  system_failure("Der Benutzername muss eingegeben werden!");
}
if (!isset($_POST['modules']) || count($_POST['modules']) == 0) {
  system_failure("Der zusätzliche Zugang muss irgendwelche Rechte erhalten!");
}

$_POST['username'] = $_SESSION['userinfo']['username'].'_'.$_POST['username'];

if (isset($_GET['id']) && (int) $_GET['id'] != 0) {
  edit_subuser($_GET['id'], $_POST['username'], $_POST['modules'], $_POST['password']);
} else {
  new_subuser($_POST['username'], $_POST['modules'], $_POST['password']);
}


if (! $debugmode)
  header('Location: subusers');


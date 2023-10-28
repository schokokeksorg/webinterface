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


if (!$debugmode) {
    header('Location: subusers');
}

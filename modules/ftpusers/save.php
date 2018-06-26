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

include('ftpusers.php');

require_role(ROLE_SYSTEMUSER);

if (isset($_GET['regular_ftp'])) {
    check_form_token('regular_ftp', $_REQUEST['token']);
    if ($_GET['regular_ftp'] == 'yes') {
        $sure = user_is_sure();
        if ($sure === null) {
            are_you_sure("regular_ftp=yes&token=".$_REQUEST['token'], "Benötigen Sie wirklich klassischen FTP-Zugriff für Ihren Benutzeraccount? Lesen Sie die Hinweise in unserem Wiki falls Sie sich nicht sicher sind.");
            return;
        } elseif ($sure === true) {
            enable_regular_ftp();
        }
    } else {
        disable_regular_ftp();
    }
    redirect('accounts');
}


if (isset($_GET['delete'])) {
    $ftpuser = load_ftpuser($_GET['delete']);

    $sure = user_is_sure();
    if ($sure === null) {
        are_you_sure("delete={$ftpuser['id']}", "Möchten Sie den FTP-Zugang »{$ftpuser['username']}« wirklich löschen?");
        return;
    } elseif ($sure === true) {
        delete_ftpuser($ftpuser['id']);
    }
    redirect('accounts');
}

$ftpuser = empty_ftpuser();

if (isset($_GET['id'])) {
    check_form_token('ftpusers_edit');
    $ftpuser = load_ftpuser($_GET['id']);
}


$ftpuser['username'] = $_REQUEST['ftpusername'];
$ftpuser['password'] = $_REQUEST['password'];
$ftpuser['homedir'] = $_REQUEST['homedir'];
if (isset($_REQUEST['active'])) {
    $ftpuser['active'] = $_REQUEST['active'];
} else {
    $ftpuser['active'] = 0;
}

if (isset($_REQUEST['forcessl'])) {
    $ftpuser['forcessl'] = $_REQUEST['forcessl'];
} else {
    $ftpuser['forcessl'] = 0;
}

if (isset($_REQUEST['server'])) {
    $ftpuser['server'] = $_REQUEST['server'];
}


save_ftpuser($ftpuser);

redirect('accounts');

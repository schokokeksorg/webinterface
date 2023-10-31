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
require_once('inc/security.php');

include('git.php');
$section = 'git_git';

if (isset($_GET['repo'])) {
    $repos = list_repos();
    if (!array_key_exists($_GET['repo'], $repos)) {
        system_failure("Es sollte ein unbekanntes Repository gelöscht werden!");
    }

    $sure = user_is_sure();
    if ($sure === null) {
        are_you_sure("repo={$_GET['repo']}", '<p>Soll das GIT-Repository »' . $_GET['repo'] . '« wirklich gelöscht werden?</p>
    <p>Alle Inhalte die in diesem Repository gespeichert sind, werden gelöscht!</p>');
    } elseif ($sure === true) {
        delete_repo($_GET['repo']);
        if (!$debugmode) {
            header('Location: git');
        }
        die();
    } elseif ($sure === false) {
        if (!$debugmode) {
            header("Location: git");
        }
        die();
    }
}

if (isset($_GET['handle'])) {
    $users = list_users();
    if (!in_array($_GET['handle'], $users)) {
        system_failure("Es sollte ein unbekannter Benutzer gelöscht werden!");
    }

    $sure = user_is_sure();
    if ($sure === null) {
        are_you_sure("handle={$_GET['handle']}", '<p>Soll der SSH-Key »' . $_GET['handle'] . '« wirklich gelöscht werden?</p>');
    } elseif ($sure === true) {
        delete_key($_GET['handle']);
        if (!$debugmode) {
            header('Location: git');
        }
        die();
    } elseif ($sure === false) {
        if (!$debugmode) {
            header("Location: git");
        }
        die();
    }
}

if (isset($_GET['foreignhandle'])) {
    $users = list_foreign_users();
    if (!in_array($_GET['foreignhandle'], $users)) {
        system_failure("Es sollte ein unbekannter Benutzer gelöscht werden!");
    }

    $sure = user_is_sure();
    if ($sure === null) {
        are_you_sure("foreignhandle={$_GET['foreignhandle']}", '<p>Soll der GIT-Benutzer »' . $_GET['foreignhandle'] . '« wirklich aus Ihrer Konfiguration werden?</p>');
    } elseif ($sure === true) {
        delete_foreign_user($_GET['foreignhandle']);
        if (!$debugmode) {
            header('Location: git');
        }
        die();
    } elseif ($sure === false) {
        if (!$debugmode) {
            header("Location: git");
        }
        die();
    }
}

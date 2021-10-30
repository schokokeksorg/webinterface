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

require_once("inc/security.php");

function system_failure($reason)
{
    if (!isset($_SESSION['role'])) {
        $_SESSION['role'] = ROLE_ANONYMOUS;
    }
    input_error($reason);
    show_page();
    die();
}


function input_error($reason)
{
    global $input_error;
    if (!isset($input_error)) {
        $input_error = [];
    }
    array_push($input_error, $reason);
}

function warning($msg)
{
    if (!isset($_SESSION['warning'])) {
        $_SESSION['warning'] = [];
    }
    array_push($_SESSION['warning'], $msg);
    $backtrace = debug_backtrace();
    DEBUG('WARNING:<br>'.$backtrace[0]['file'].':'.$backtrace[0]['line'].': '.$msg);
}

function success_msg($msg)
{
    if (!isset($_SESSION['success_msg'])) {
        $_SESSION['success_msg'] = [];
    }
    array_push($_SESSION['success_msg'], $msg);
}


function we_have_an_error()
{
    global $input_error;

    if (isset($input_error)) {
        return (count($input_error) > 0);
    } else {
        return 0;
    }
}


function get_messages()
{
    $messages = '';
    global $input_error;
    if (isset($input_error)) {
        $messages .= '<div class="error">
    <h3>Fehler</h3>
    <p>Folgende Fehler wurden festgestellt: </p>
    <ul>
    ';
        foreach ($input_error as $error) {
            $messages .= '<li>'.nl2br(filter_output_html($error))."</li>\n";
        }
        $messages .= '</ul>
    </div>';
    }
    if (isset($_SESSION['warning'])) {
        $messages .= '<div class="warning">
    <ul>
    ';
        foreach ($_SESSION['warning'] as $msg) {
            $messages .= '<li>'.nl2br(filter_output_html($msg))."</li>\n";
        }
        $messages .= '</ul>
    </div>';
        unset($_SESSION['warning']);
    }
    if (isset($_SESSION['success_msg'])) {
        $messages .= '<div class="success">
    <ul>
    ';
        foreach ($_SESSION['success_msg'] as $msg) {
            $messages .= '<li>'.nl2br(filter_output_html($msg))."</li>\n";
        }
        $messages .= '</ul>
    </div>';
        unset($_SESSION['success_msg']);
    }
    return $messages;
}

function show_messages()
{
    echo get_messages();
}

function require_role($roles)
{
    if (! is_array($roles)) {
        $roles = [$roles];
    }
    $allow = false;
    foreach ($roles as $role) {
        if ($role & $_SESSION['role']) {
            $allow = true;
        }
    }
    if (! $allow) {
        if ($_SESSION['role'] == ROLE_ANONYMOUS) {
            login_screen();
        } else {
            $backtrace = debug_backtrace();
            DEBUG($backtrace[0]['file'].':'.$backtrace[0]['line'].': Current user does not have any of the required roles: '.implode(",", $roles));
            login_screen("Diese Seite können Sie mit Ihren aktuellen Zugriffsrechten nicht benutzen, bitte melden Sie sich mit den benötigten Zugriffsrechten an!");
        }
    }
}


function login_screen($why = null)
{
    $failed = count_failed_logins();
    if ($failed > 5) {
        global $title;
        $title = '';
        system_failure("Zu viele fehlgeschlagenen Login-Versuche! Bitte warten Sie einige Minuten bis zum nächsten Versuch!");
    }
    if (! $why) {
        if (isset($_COOKIE['CLIENTCERT_AUTOLOGIN']) && $_COOKIE['CLIENTCERT_AUTOLOGIN'] == '1') {
            redirect("/certlogin/index.php?destination=".urlencode($_SERVER['REQUEST_URI']));
        }
    }
    require_once('inc/theme.php');
    if ($why) {
        warning($why);
    }
    show_page('login');
    die();
}

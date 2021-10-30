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

require_once('inc/base.php');
require_once('vhosts.php');

require_once('inc/debug.php');
global $debugmode;

check_form_token('aliases_toggle', $_GET['formtoken']);

if (isset($_GET['aliaswww'])) {
    $aliaswww = (bool) ((int) $_GET['aliaswww']);

    $alias = get_alias_details($_GET['alias']);
    DEBUG($alias);
    $old_options = explode(',', $alias['options']);
    $new_options = [];
    foreach ($old_options as $op) {
        if ($op !== '' && $op != 'aliaswww') {
            array_push($new_options, $op);
        }
    }
    if ($aliaswww) {
        array_push($new_options, 'aliaswww');
    }

    DEBUG($old_options);
    DEBUG($new_options);
    $alias['options'] = implode(',', $new_options);
    DEBUG('New options: '.$alias['options']);

    $alias['domainid'] = $alias['domain_id'];
    save_alias($alias);

    if (! $debugmode) {
        header('Location: aliases?vhost='.$alias['vhost']);
    }
}
if (isset($_GET['forward'])) {
    $forward = (bool) ((int) $_GET['forward']);

    $alias = get_alias_details($_GET['alias']);
    DEBUG($alias);
    $old_options = explode(',', $alias['options']);
    $new_options = [];
    foreach ($old_options as $op) {
        if ($op !== '' && $op != 'forward') {
            array_push($new_options, $op);
        }
    }
    if ($forward) {
        array_push($new_options, 'forward');
    }

    DEBUG($old_options);
    DEBUG($new_options);
    $alias['options'] = implode(',', $new_options);
    DEBUG('New options: '.$alias['options']);

    $alias['domainid'] = $alias['domain_id'];
    save_alias($alias);

    if (! $debugmode) {
        header('Location: aliases?vhost='.$alias['vhost']);
    }
}

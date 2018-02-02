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

if (isset($_GET['type'])) {
    $caller = 'detail';
    if (isset($_REQUEST['backto'])) {
        $caller = $_REQUEST['backto'];
    }
    $_SESSION['domains_choose_redirect'] = $caller;

    $function = 'owner';
    if ($_GET['type'] == 'admin_c') {
        if (isset($_GET['detach'])) {
            $_SESSION['domains_'.$caller.'_detach'] = $_GET['detach'];
        }
        $function = 'admin_c';
    }
    $t = 'Inhaber';
    if ($function == 'admin_c') {
        $t = 'Verwalter';
    }
    $_SESSION['contacts_choose_header'] = 'Wählen Sie einen neuen '.$t.' für die Domain '.$_SESSION['domains_'.$caller.'_domainname'];
    $_SESSION['contacts_choose_key'] = 'domains_'.$caller.'_'.$function;
    $_SESSION['contacts_choose_redirect'] = '../domains/choose';
    redirect('../contacts/choose');
} else {
    unset($_SESSION['contacts_choose_key']);
    unset($_SESSION['contacts_choose_header']);
    unset($_SESSION['contacts_choose_redirect']);
    $backto = $_SESSION['domains_choose_redirect'];
    unset($_SESSION['domains_choose_redirect']);
    redirect($backto);
}



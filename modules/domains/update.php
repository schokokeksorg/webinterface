<?php

/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/debug.php');
require_once('inc/icons.php');

require_once('class/domain.php');
require_once('domains.php');

require_role(ROLE_CUSTOMER);

if (!isset($_REQUEST['action'])) {
    system_failure("Falscher Aufruf");
}

if ($_REQUEST['action'] == 'chguser') {
    change_user((int) $_REQUEST['id'], $_REQUEST['domainuser']);
    redirect('detail?id=' . (int) $_REQUEST['id']);
}

if ($_REQUEST['action'] == 'ownerchange') {
    if (!(isset($_POST['accept']) && $_POST['accept'] == '1')) {
        redirect('detail?error=1');
    }
    check_form_token('domains_update');
    $dom = new Domain($_SESSION['domains_detail_domainname']);
    if (!$dom) {
        system_failure("Keine Domain gewählt!");
    }

    DEBUG($dom);
    domain_ownerchange($_SESSION['domains_detail_domainname'], $_SESSION['domains_detail_owner'], $_SESSION['domains_detail_admin_c']);


    unset($_SESSION['domains_detail_domainname']);
    unset($_SESSION['domains_detail_owner']);
    unset($_SESSION['domains_detail_admin_c']);
    unset($_SESSION['domains_detail_detach']);

    redirect('domains');
}

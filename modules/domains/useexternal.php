<?php

/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/security.php');
require_once('inc/icons.php');

require_once('domains.php');
require_role(ROLE_CUSTOMER);

title("Externe Domain hinzufügen");
$section = 'domains_domains';

if (!isset($_REQUEST['domain'])) {
    system_failure('Kein Domainname übergeben');
}
$request = idn_to_utf8($_REQUEST['domain'], 0, INTL_IDNA_VARIANT_UTS46);
if (substr($request, 0, 4) == 'www.') {
    $request = str_replace('www.', '', $request);
}
verify_input_hostname_utf8($request);
$punycode = idn_to_ascii($request, 0, INTL_IDNA_VARIANT_UTS46);
if (!check_domain($punycode)) {
    warning("Ungültiger Domainname: " . filter_output_html($request));
    redirect('');
}

$id = insert_domain_external($request, ($_REQUEST['dns'] === 'enable'), ($_REQUEST['email'] === 'enable'));

redirect('detail?id=' . $id);

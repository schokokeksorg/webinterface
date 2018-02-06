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

require_once('inc/security.php');
require_once('inc/icons.php');

require_once('domains.php');
require_role(ROLE_CUSTOMER);

title("Externe Domain hinzufügen");
$section='domains_domains';

if (!isset($_REQUEST['domain'])) {
    system_failure('Kein Domainname übergeben');
}
$request = idn_to_utf8($_REQUEST['domain'], 0, INTL_IDNA_VARIANT_UTS46);
if (substr($request, 0, 4) == 'www.') {
    $request = str_replace('www.', '', $request);
}
verify_input_general($request);
$punycode = idn_to_ascii($request, 0, INTL_IDNA_VARIANT_UTS46);
if (!check_domain($punycode)) {
    warning("Ungültiger Domainname: ".filter_input_general($request));
    redirect('');
}

$id = insert_domain_external($request, ($_REQUEST['dns'] === 'enable'), ($_REQUEST['email'] === 'enable'));

redirect('detail?id='.$id);



<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('session/start.php');

require_once('inc/security.php');
require_once('inc/icons.php');

require_once('adddomain.php');

require_role(ROLE_CUSTOMER);

title("Domain hinzufügen");
$section = 'adddomain_search';


check_form_token('adddomain_add');

register_domain($_REQUEST['domain'], $_REQUEST['uid']);

success_msg('Domain »'.filter_output_html($_REQUEST['domain']).'« wurde eingetragen!');

redirect('search');

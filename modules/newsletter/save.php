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

require_once("includes/newsletter.php");
require_once("inc/security.php");
require_once("inc/base.php");


if ((isset($_REQUEST['newsletter']) && $_REQUEST['newsletter'] == 'no') || (isset($_REQUEST['recipient']) && $_REQUEST['recipient'] == "")) {
    $sure = user_is_sure();
    if ($sure === null) {
        check_form_token('newsletter');
        are_you_sure("newsletter=no", "Wenn Sie keinen Newsletter abonnieren, erhalten Sie von uns keine Informationen zu laufenden Änderungen bei schokokeks.org. Beachten Sie bitte dennoch regelmäßig die Einträge auf dieser Website, unser Weblog und unsere Status-Seite. Möchten Sie den Newsletter wirklich abbestellen?");
    } elseif ($sure === true) {
        set_newsletter_address(null);
        if (! $debugmode) {
            header('Location: newsletter');
        }
    } elseif ($sure === false) {
        if (! $debugmode) {
            header('Location: newsletter');
        }
    }
} else {
    check_form_token('newsletter');
    if (! check_emailaddr($_REQUEST['recipient']) || filter_input_general($_REQUEST['recipient']) != $_REQUEST['recipient']) {
        system_failure("Keine gültige E-Mail-Adresse!");
    }
    set_newsletter_address($_REQUEST['recipient']);
    if (! $debugmode) {
        header('Location: newsletter');
    }
}

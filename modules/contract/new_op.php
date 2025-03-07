<?php

/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('contract.php');
require_once('inc/debug.php');
require_once('inc/icons.php');

require_role([ROLE_CUSTOMER]);

title("Neuen AV-Vertrag abschließen");


output('<p>Bitte kontrollieren Sie den Vertragstext und bestätigen Sie anschließend den Abschluss des Vertrags.</p>
<iframe class="contract_preview" src="html?id=preview&type=orderprocessing"></iframe>');

$html = '<p><input type="checkbox" name="agree" value="yes" id="agree"> <label for="agree">Ja, ich stimme diesem Vertrag zu und möchte diesen hiermit unterzeichnen</label></p>
<p><input type="submit" value="Unterzeichnen"></p>';

output(html_form('contract_new_op', 'sign', 'type=op', $html));

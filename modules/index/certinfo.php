<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

title('Login über SSL-Client-Zertifikat');
output('<p>Sie können Sich an diesem Interface auch per SSL-Client-Zertifikat anmelden. Dazu müssen Sie dieses Zertifikat vorab hinterlegt haben.</p>

<p>Um den Login über ein Client-Zertifikat zu nutzen, nutzen Sie bitte diesen Link:</p>
<p><strong>'.internal_link('../../certlogin/', 'Login über SSL-Client-Zertifikat').'</strong></p>
');

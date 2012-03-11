<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2012 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

title('Login über SSL-Client-Zertifikat');
output('<p>Sie können Sich an diesem Interface auch per SSL-Client-Zertifikat anmelden. Dazu müssen Sie dieses Zertifikat vorab hinterlegt haben.</p>

<div class="error"><strong>Hinweis:</strong><br />
Sie benötigen für den Login per Zertifikat einen Browser, der die so genannte
TLS-Renegotiation nach dem Standard RFC 5746 unterstützt. Firefox kann dies
ab Version 3.6.2.
<a href="http://www.phonefactor.com/sslgap/ssl-tls-authentication-patches">Hier</a>
finden Sie weitere Informationen zur Unterstützung in anderen Browsern.
</div>

<p>Um den Login über ein Client-Zertifikat zu nutzen, nutzen Sie bitte diesen Link:</p>
<p><strong>'.internal_link('../../certlogin/', 'Login über SSL-Client-Zertifikat').'</strong></p>
');




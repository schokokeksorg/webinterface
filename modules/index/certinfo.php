<?php

$title = 'Login über SSL-Client-Zertifikat';
output('<h3>Login über SSL-Client-Zertfikat</h3>');
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




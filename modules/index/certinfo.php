<?php

$title = 'Login über SSL-Client-Zertifikat';
output('<h3>Login über SSL-Client-Zertfikat</h3>');
output('<p>Sie können Sich an diesem Interface auch per SSL-Client-Zertifikat anmelden. Dazu müssen Sie dieses Zertifikat vorab hinterlegt haben.</p>

<div class="error"><strong>Hinweis:</strong><br />
Aufgrund einer aktuellen Sicherheits-Lücke wurde in vielen Browsern die so genannte TLS-Renegotiation abgeschaltet. Ohne diese Funktion ist ein Login über Client-Zertifikate technisch nicht möglich.
Mit einigen aktuellen Browser-Versionen ist der Login mittels Client-Zertifikat momentan nicht möglich.

<a href="http://groups.google.com/group/mozilla.dev.tech.crypto/browse_thread/thread/42c17928ea4fc374">Informationen und Lösungsmöglichkeit zum Mozilla-Firefox</a>
</div>

<p>Um den Login über ein Client-Zertifikat zu nutzen, nutzen Sie bitte diesen Link:</p>
<p><strong>'.internal_link('../../certlogin/', 'Login über SSL-Client-Zertifikat').'</strong></p>
');




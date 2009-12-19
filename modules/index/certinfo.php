<?php

$title = 'Login über SSL-Client-Zertifikat';
output('<h3>Login über SSL-Client-Zertfikat</h3>');
output('<p>Sie können Sich an diesem Interface auch per SSL-Client-Zertifikat anmelden. Dazu müssen Sie dieses Zertifikat vorab hinterlegt haben.</p>

<div class="error"><strong>Hinweis:</strong><br />
Aufgrund einer aktuellen Sicherheits-Lücke wurde in vielen Browsern die so genannte TLS-Renegotiation abgeschaltet. Ohne diese Funktion ist ein Login über Client-Zertifikate technisch nicht möglich.
Es gibt daher momentan viele aktuelle Browser, mit denen der Login via Client-Zertifikat nicht benutzt werden kann.</div>

<p>Um den Login über ein Client-Zertifikat zu nutzen, nutzen Sie bitte diesen Link:</p>
<p><strong>'.internal_link('../../certlogin/', 'Login über SSL-Client-Zertifikat').'</strong></p>
');




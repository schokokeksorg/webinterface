<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2013 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

title('Login über SSL-Client-Zertifikat');

$path = config('jquery_ui_path');
html_header('
<link rel="stylesheet" href="'.$path.'/themes/base/jquery-ui.css">
<script type="text/javascript" src="'.$path.'/jquery-1.9.0.js" ></script>
<script type="text/javascript" src="'.$path.'/ui/jquery-ui.js" ></script>
<script>
  function redirect(status) {
    if (status == "ok") {
      window.location.href="../../go/index/index";
    } else {
      window.location.href="../../certlogin/";
    }
  }
  $.get("../../certlogin/ajax.php", redirect);
</script>
');

output('<p>Sie werden nun über Ihr SSL-Client-Zertifikat eingeloggt. Möglicherweise werden Sie von Ihrem Browser zunächst gebeten, ein Zertifkkat auszuwählen.</p>');

output('<p>Sollte der Login nicht funktionieren, klicken Sie bitte diesen Link:</p>
<p><strong>'.internal_link('../../certlogin/', 'Login über SSL-Client-Zertifikat').'</strong></p>
');




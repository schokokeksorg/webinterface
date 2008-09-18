<?php

require_once('inc/base.php');
require_once('inc/debug.php');

require_once('session/start.php');
require_once('crm.php');

require_role(ROLE_SYSADMIN);


html_header('<script type="text/javascript" src="'.$prefix.'js/ajax.js" ></script>
<script type="text/javascript">
<!--

function got_response() {
  if (xmlHttp.readyState == 4) {
    document.getElementById(\'response\').innerHTML = xmlHttp.responseText;
  }
}

// -->
</script>
');

output(html_form('crm_test', '', '', '<input type="text" id="query" onkeyup="ajax_request(\'crm_ajax\', \'q=\'+document.getElementById(\'query\').value, got_response)" />
'));
output('<div id="response"></div>');


$customers = array_unique(find_customer('gmx.de'));
sort($customers);
DEBUG($customers);



<?php

require_once('inc/base.php');
require_once('inc/debug.php');

require_once('class/customer.php');

require_once('session/start.php');
require_once('crm.php');

require_role(ROLE_SYSADMIN);


$debug = '';
if ($debugmode)
  $debug = 'debug&amp;';

html_header('<script type="text/javascript" src="'.$prefix.'js/ajax.js" ></script>
<script type="text/javascript">
<!--

function doRequest() {
  ajax_request(\'crm_ajax\', \''.$debug.'q=\'+document.getElementById(\'query\').value, got_response)
}

function keyPressed() {
  if(window.mytimeout) window.clearTimeout(window.mytimeout);
  window.mytimeout = window.setTimeout(doRequest, 500);
  return true;
}

function got_response() {
  if (xmlHttp.readyState == 4) {
    document.getElementById(\'response\').innerHTML = xmlHttp.responseText;
  }
}

// -->
</script>
');


output('<h3>Customer Relationship Management</h3>');



output(html_form('crm_main', '', '', 'Kunde nach Stichwort suchen: <input type="text" id="query" onkeyup="keyPressed()" />
'));
output('<div id="response"></div>');

if (isset($_SESSION['crm_customer'])) {
  $cid = $_SESSION['crm_customer'];
  $cust = new Customer($cid);

  $hostingcont = hosting_contracts($cust->id);
  $hosting = '<ul>';
  foreach ($hostingcont AS $h) {
    $hosting .= '<li>Hosting: ';
  }
  $hosting .= '</ul>';

  output('<h3>Aktueller Kunde</h3>
<div><strong>'.$cust->fullname.'</strong><br />
Firma: '.$cust->firma.'<br />
Name: '.$cust->vorname.' '.$cust->nachname.'<br />
Adresse: '.$cust->adresse.' - '.$cust->plz.' '.$cust->ort.'</div>


<h3>Kundendaten</h3>


<h4>Letzte Rechnungen</h4>


<h4>Kommende Rechnungsposten</h4>

');
   
  output ( print_r($cust, true) );
}





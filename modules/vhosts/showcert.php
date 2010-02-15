<?php

require_once('certs.php');
require_role(ROLE_SYSTEMUSER);

$mode = 'cert';
if ($_REQUEST['mode'] == 'csr')
  $mode = 'csr';


$section = 'vhosts_certs';


if ($mode == 'csr')
{
  $data = csr_details($_REQUEST['id']);
  $cert = $data['csr'];
  $key = $data['key'];


  $title = 'CSR anzeigen';
  output("<h3>CSR anzeigen</h3>
  <p>Untenstehend sehen Sie Ihren automatisch erzeugten CSR (»certificate signing request«) und evtl.
  den dazu gehörigen privaten Schlüssel. Sofern Sie den privaten Schlüssel auf Ihrer Festplatte 
  speichern, stellen Sie bitte sicher, dass dieser vor unbefugtem Zugriff geschützt ist. Der
  private Schlüssel ist selbst <strong>nicht verschlüsselt</strong> und nicht mit einem 
  Passwort geschützt.</p>");

  output("<h4>CSR</h4>
  <textarea cols=\"70\" rows=\"20\">
{$cert}
</textarea>");
  
  if (isset($_REQUEST['private']) && $_REQUEST['private'] == 'yes')
  {
    output("<h4>privater Schlüssel</h4>
<textarea cols=\"70\" rows=\"20\">
{$key}
</textarea>");
  }
  else
  {
    output('<p>'.internal_link('', 'privaten Schlüssel auch anzeigen', "mode={$_REQUEST['mode']}&id={$_REQUEST['id']}&private=yes").'</p>');
  }


  addnew('certfromcsr', 'Unterschriebenes Zertifikat eingeben', "id={$_REQUEST['id']}");


}
else
{
  $data = cert_details($_REQUEST['id']);
  $cert = $data['cert'];
  $key = $data['key'];

  $title = 'Zertifikat anzeigen';
  output("<h3>Zertifikat anzeigen</h3>
  <p>Untenstehend sehen Sie Ihr SSL-Zertifikat und evtl. den dazu gehörigen privaten 
  Schlüssel. Sofern Sie den privaten Schlüssel auf Ihrer Festplatte speichern, stellen 
  Sie bitte sicher, dass dieser vor unbefugtem Zugriff geschützt ist. Der private 
  Schlüssel ist selbst <strong>nicht verschlüsselt</strong> und nicht mit einem 
  Passwort geschützt.</p>");

  output("<h4>Zertifikat</h4>
  <textarea cols=\"70\" rows=\"20\">
{$cert}
</textarea>");
  
  if (isset($_REQUEST['private']) && $_REQUEST['private'] == 'yes')
  {
    output("<h4>privater Schlüssel</h4>
<textarea cols=\"70\" rows=\"20\">
{$key}
</textarea>");
  }
  else
  {
    output('<p>'.internal_link('', 'privaten Schlüssel auch anzeigen', "mode={$_REQUEST['mode']}&id={$_REQUEST['id']}&private=yes").'</p>');
  }




}












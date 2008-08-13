<?php

require_once('inc/base.php');
require_once('inc/security.php');

require_once('class/domain.php');

require_role(ROLE_CUSTOMER);

require_once('dnsinclude.php');

$section = 'dns_dns';

$data = array();
$type = NULL;

$new = false;
if ($_REQUEST['id'] == 'new')
{
  $new = true;
  $data = blank_dns_record($_REQUEST['type']);
  $domain = new Domain((int) $_REQUEST['dom']);
  $type = $_POST['type'];
  if (! in_array($type, $valid_record_types))
    system_failure('Ungültiger Record-Typ!');
  $data['domain'] = $domain->id;
}

if (! $new)
{
  $data = get_dns_record($_REQUEST['id']);
  $type = $data['type'];
  if (! in_array($type, $valid_record_types))
    system_failure('Ungültiger Record-Typ!');
}




if ($new)
  $output .= '<h3>DNS-Record erstellen</h3>';
else
  $output .= '<h3>DNS-Record bearbeiten</h3>';


$action = 'create';
if (! $new)
  $action = 'edit&id='.(int)$_REQUEST['id'];
  
$submit = 'Speichern';
if ($new) 
  $submit = 'Anlegen';

$domain = new Domain( (int) $data['domain'] );


$output .= html_form('dns_record_edit', 'save', 'type=dns&action='.$action, 
'<p>
<label for="hostname">Hostname:</label>&#160;<input type="text" name="hostname" id="hostname" value="'.$data['hostname'].'" />&#160;<strong>.'.$domain->fqdn.'</strong><p>
<p>Typ: 
<p><input type="submit" value="'.$submit.'" /></p>
</p>');

?>

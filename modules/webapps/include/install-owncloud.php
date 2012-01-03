<?php

require_once('inc/debug.php');

require_once('webapp-installer.php');


function validate_data($post)
{
  DEBUG('Validating Data:');
  DEBUG($post);
  $fields = array('adminpass');
  foreach ($fields AS $field)
    if ((! isset($post[$field])) || $post[$field] == '')
      system_failure('Nicht alle Werte angegeben ('.$field.')');

  $adminpass = sha1($post['adminpass']);
  
  $data = "adminpass={$adminpass}";
  DEBUG($data);
  return $data;
}



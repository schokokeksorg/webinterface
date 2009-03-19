<?php

require_once('inc/debug.php');

require_once('webapp-installer.php');


function validate_data($post)
{
  DEBUG('Validating Data:');
  DEBUG($post);
  $fields = array('adminuser', 'adminpassword', 'adminemail', 'sitename', 'siteemail');
  foreach ($fields AS $field)
    if ((! isset($post[$field])) || $post[$field] == '')
      system_failure('Nicht alle Werte angegeben ('.$field.')');

  $dbdata = create_webapp_mysqldb('drupal6', $post['sitename']);

  $passwordhash = md5( $post['adminpassword'] );
  
  $data = "adminuser={$post['adminuser']}
adminpassword={$passwordhash}
adminemail={$post['adminemail']}
sitename={$post['sitename']}
siteemail={$post['siteemail']}
dbname={$dbdata['dbname']}
dbuser={$dbdata['dbuser']}
dbpass={$dbdata['dbpass']}";
  DEBUG($data);
  return $data;
}



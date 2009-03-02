<?php

require_once('inc/debug.php');

require_once('webapp-installer.php');


function validate_data($post)
{
  DEBUG('Validating Data:');
  DEBUG($post);
  $fields = array('adminuser', 'adminpassword', 'adminemail', 'sitename', 'siteemail', 'dbhandle');
  foreach ($fields AS $field)
    if ((! isset($post[$field])) || $post[$field] == '')
      system_failure('Nicht alle Werte angegeben ('.$field.')');

  $username = mysql_real_escape_string($_SESSION['userinfo']['username']);
  $dbname = $username.'_'.$post['dbhandle'];
  $dbpassword = create_webapp_mysqldb($post['dbhandle']);

  $passwordhash = md5( $post['adminpassword'] );
  
  $data = "adminuser={$post['adminuser']}
adminpassword={$passwordhash}
adminemail={$post['adminemail']}
sitename={$post['sitename']}
siteemail={$post['siteemail']}
dbname={$dbname}
dbuser={$dbname}
dbpass={$dbpassword}";
  DEBUG($data);
  return $data;
}



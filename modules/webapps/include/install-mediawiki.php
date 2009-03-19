<?php

require_once('inc/debug.php');

require_once('webapp-installer.php');


function validate_data($post)
{
  DEBUG('Validating Data:');
  DEBUG($post);
  $fields = array('adminuser', 'adminpassword', 'adminemail', 'wikiname');
  foreach ($fields AS $field)
    if ((! isset($post[$field])) || $post[$field] == '')
      system_failure('Nicht alle Werte angegeben ('.$field.')');

  $dbdata = create_webapp_mysqldb('mediawiki', $post['wikiname']);

  $salt = random_string(8);
  $salthash = ':B:' . $salt . ':' . md5( $salt . '-' . md5( $post['adminpassword'] ));
  
  $data = "adminuser={$post['adminuser']}
adminpassword={$salthash}
adminemail={$post['adminemail']}
wikiname={$post['wikiname']}
dbname={$dbdata['dbname']}
dbuser={$dbdata['dbuser']}
dbpass={$dbdata['dbpass']}";
  DEBUG($data);
  return $data;
}



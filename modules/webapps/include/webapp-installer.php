<?php

require_once('inc/base.php');

function create_new_webapp($appname, $directory, $url, $data)
{
  $username = mysql_real_escape_string($_SESSION['userinfo']['username']);
  $appname = mysql_real_escape_string($appname);
  $directory = mysql_real_escape_string($directory);
  $url = mysql_real_escape_string($url);
  $data = mysql_real_escape_string($data);
  db_query("INSERT INTO vhosts.webapp_installer VALUES (NULL, '{$appname}', '{$directory}', '{$url}', 'new', '{$username}', '{$data}')");
}


function create_webapp_mysqldb($handle)
{
  // dependet auf das mysql-modul
  require_once('modules/mysql/include/mysql.php'); 
  
  $username = mysql_real_escape_string($_SESSION['userinfo']['username']);
  if ($handle == '')
    input_error('Kein Datenbank-Handle angegeben');
  $handle = $username.'_'.$handle;
  
  if (! validate_mysql_username($handle))
  {
    system_failure('Ungültiges MySQL-Handle');
  }

  if (has_mysql_user($handle) || has_mysql_database($handle))
  {
    system_failure('Eine Datenbank oder einen Datenbank-Benutzer mit diesem Namen gibt es bereits!');
  }

  create_mysql_database($handle);
  create_mysql_account($handle);
  set_mysql_access($handle, $handle, true);
  $password = random_string(10);
  set_mysql_password($handle, $password);
  return $password; 
}



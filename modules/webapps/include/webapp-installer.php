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


function request_update($appname, $directory, $url)
{
  $username = mysql_real_escape_string($_SESSION['userinfo']['username']);
  $appname = mysql_real_escape_string($appname);
  $directory = mysql_real_escape_string($directory);
  $url = maybe_null(mysql_real_escape_string($url));
  db_query("INSERT INTO vhosts.webapp_installer VALUES (NULL, '{$appname}', '{$directory}', {$url}, 'old', '{$username}', NULL)");
}


function upgradeable($appname, $version)
{
  DEBUG("Is {$appname}-{$version} upgradeable?");
  if ($appname == 'Drupal')
  {
    DEBUG("found Drupal!");
    if (substr($version, 0, 2) == '6.')
    {
      DEBUG("found Drupal-6.*!");
      return 'drupal6';
    }
    DEBUG("Version: ".substr($version, 0, 2));
  }
  DEBUG("found no upgradeable webapp!");
  return NULL;
}


function get_url_for_dir($docroot, $cutoff = '')
{
  if (substr($docroot, -1) == '/')
    $docroot = substr($docroot, 0, -1);
  $docroot = mysql_real_escape_string($docroot);
  $result = db_query("SELECT `ssl`, IF(FIND_IN_SET('aliaswww', options), CONCAT('www.',fqdn), fqdn) AS fqdn FROM vhosts.v_vhost WHERE docroot IN ('{$docroot}', '{$docroot}/') LIMIT 1");
  if (mysql_num_rows($result) < 1)
  {
    if (!strstr($docroot, '/'))
      return NULL;
    return get_url_for_dir(substr($docroot, 0, strrpos($docroot, '/')), substr($docroot, strrpos($docroot, '/')).$cutoff);
  } 
  $tmp = mysql_fetch_assoc($result);
  $prefix = 'http://';
  if ($tmp['ssl'] == 'forward' || $tmp['ssl'] == 'https')
    $prefix = 'https://';
  return $prefix.$tmp['fqdn'].$cutoff;
}


function create_webapp_mysqldb($application, $sitename)
{
  // dependet auf das mysql-modul
  require_once('modules/mysql/include/mysql.php'); 
  
  $username = mysql_real_escape_string($_SESSION['userinfo']['username']);
  $description = "Automatisch erzeugte Datenbank für {$application} ({$sitename})";
  
  // zuerst versuchen wir username_webappname. Wenn das nicht klappt, dann wird hochgezählt
  $handle = $username.'_'.$application;
  
  if (validate_mysql_username($handle) && validate_mysql_dbname($handle) && ! (has_mysql_user($handle) || has_mysql_database($handle)))
  {
    create_mysql_database($handle, $description);
    create_mysql_account($handle, $description);
    set_mysql_access($handle, $handle, true);
    $password = random_string(10);
    set_mysql_password($handle, $password);
    return array('dbuser' => $handle, 'dbname' => $handle, 'dbpass' => $password);
  }

  for ($i = 0; $i < 100 ; $i++) {
    $handle = $username.'_'.$i;
    if (validate_mysql_username($handle) && validate_mysql_dbname($handle) && ! (has_mysql_user($handle) || has_mysql_database($handle)))
    {
      create_mysql_database($handle);
      create_mysql_account($handle);
      set_mysql_access($handle, $handle, true);
      $password = random_string(10);
      set_mysql_password($handle, $password);
      return array('dbuser' => $handle, 'dbname' => $handle, 'dbpass' => $password);
    }
  }
  system_failure('Konnte keine Datenbank erzeugen. Bitte melden Sie diesen Umstand den Administratoren!');
}


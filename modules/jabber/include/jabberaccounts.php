<?php

require_once("inc/debug.php");
require_once("inc/db_connect.php");
require_once("inc/security.php");


function get_jabber_accounts() {
  require_role(ROLE_CUSTOMER);
  $customerno = (int) $_SESSION['customerinfo']['customerno'];
  $query = "SELECT id, created, local, domain FROM jabber.accounts WHERE customerno='$customerno' AND `delete`=0;";
  DEBUG($query);
  $result = mysql_query($query);
  $accounts = array();
  if (@mysql_num_rows($result) > 0)
    while ($acc = @mysql_fetch_object($result))
      array_push($accounts, array('id'=> $acc->id, 'created' => $acc->created, 'local' => $acc->local, 'domain' => $acc->domain));
  return $accounts;
}



function get_jabberaccount_details($id)
{
  require_role(ROLE_CUSTOMER);
  $customerno = (int) $_SESSION['customerinfo']['customerno'];

  $id = (int) $id;

  $query = "SELECT id, local, domain FROM jabber.accounts WHERE customerno={$customerno} AND id={$id} LIMIT 1";
  DEBUG($query);
  $result = mysql_query($query);
  if (mysql_num_rows($result) != 1)
    system_failure("Invalid account");
  $data = mysql_fetch_assoc($result);
  $data['domain'] = get_domain_name($data['domain']);
  return $data;
}



function create_jabber_account($local, $domain, $password)
{
  require_role(ROLE_CUSTOMER);
  $customerno = (int) $_SESSION['customerinfo']['customerno'];

  $local = mysql_real_escape_string( filter_input_username($local) );
  $domain = (int) $domain;
  $password = mysql_real_escape_string( filter_shell( $password ) );
  
  if ($domain > 0)
  {
    $query = "SELECT id FROM kundendaten.domains WHERE kunde={$customerno} AND jabber=1 AND id={$domain};";
    DEBUG($query);
    $result = mysql_query($query);
    if (mysql_num_rows($result) == 0)
    {
      logger("modules/jabber/include/jabberaccounts.php", "jabber", "attempt to create account for invalid domain »{$domain}«");
      system_failure("Invalid domain!");
    }
  }

  $domainquery = "domain={$domain}";
  if ($domain == 0)
  {
    $domain = 'NULL';
    $domainquery = 'domain IS NULL'; 
  }
  $query = "SELECT id FROM jabber.accounts WHERE local='{$local}' AND {$domainquery}";
  DEBUG($query);
  $result = mysql_query($query);
  if (mysql_num_rows($result) > 0)
  {
    logger("modules/jabber/include/jabberaccounts.php", "jabber", "attempt to create already existing account »{$local}@{$domain}«");
    system_failure("Diesen Account gibt es bereits!");
  }

  $query = "INSERT INTO jabber.accounts (customerno,local,domain,password) VALUES ({$customerno}, '{$local}', {$domain}, '{$password}');";
  DEBUG($query);
  mysql_query($query);
  logger("modules/jabber/include/jabberaccounts.php", "jabber", "created account »{$local}@{$domain}«");
}



function change_jabber_password($id, $newpass)
{
  require_role(ROLE_CUSTOMER);
  $customerno = (int) $_SESSION['customerinfo']['customerno'];
  $id = (int) $id;
  $newpass = mysql_real_escape_string( filter_shell( $newpass ) );
  
  $query = "UPDATE jabber.accounts SET password='{$newpass}' WHERE customerno={$customerno} AND id={$id} LIMIT 1";
  DEBUG($query);
  mysql_query($query);
  logger("modules/jabber/include/jabberaccounts.php", "jabber", "changed password for account  »{$id}«");
}



function delete_jabber_account($id)
{
  require_role(ROLE_CUSTOMER);
  $customerno = (int) $_SESSION['customerinfo']['customerno'];

  $id = (int) $id;

  $query = "UPDATE jabber.accounts SET `delete`=1 WHERE customerno={$customerno} AND id={$id} LIMIT 1";
  DEBUG($query);
  mysql_query($query);
  logger("modules/jabber/include/jabberaccounts.php", "jabber", "deleted account »{$id}«");
}

?>

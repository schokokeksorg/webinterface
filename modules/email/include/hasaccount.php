<?php

require_once('inc/base.php');

function user_has_accounts()
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $result = db_query("SELECT id from `mail`.`mailaccounts` WHERE uid=$uid");
  DEBUG(mysql_num_rows($result)." accounts");
  return (mysql_num_rows($result) > 0);
}

if (! function_exists("user_has_vmail_domain"))
{
  function user_has_vmail_domain()
  {
        $role = $_SESSION['role'];
        if (! ($role & ROLE_SYSTEMUSER)) {
                return false;
        }
        $uid = (int) $_SESSION['userinfo']['uid'];
        $result = db_query("SELECT COUNT(*) FROM mail.v_vmail_domains WHERE useraccount='{$uid}'");
        $row = mysql_fetch_array($result);
        $count = $row[0];
        DEBUG("User has {$count} vmail-domains");
        return ( (int) $count > 0 );
  }
}

function user_has_regular_domain()
{
  $result = db_query("SELECT id FROM kundendaten.domains AS dom WHERE id NOT IN (SELECT domain FROM mail.virtual_mail_domains WHERE hostname IS NULL)");
  return (mysql_num_rows() > 0);
}


?>

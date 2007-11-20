<?php
require_once('inc/base.php');
require_once('inc/debug.php');


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


function empty_account()
{
	$account = array(
		'id' => NULL,
		'local' => '',
		'domain' => NULL,
		'type' => 'mailbox',
		'data' => NULL,
		'spamfilter' => NULL,
		'virusfilter' => NULL,
		'spamexpire' => 7,
		'virusexpire' => 7
		);
	return $account;

}

function get_account_details($id)
{
	$id = (int) $id;
	$uid = (int) $_SESSION['userinfo']['uid'];
	$result = db_query("SELECT id, local, domainid as domain, type, data, spamfilter, virusfilter from mail.v_virtual_mail WHERE useraccount='{$uid}' AND id={$id} LIMIT 1");
	if (mysql_num_rows($result) == 0)
		system_failure('Ungültige ID oder kein eigener Account');
	return mysql_fetch_assoc($result);;
	
}

function get_vmail_accounts()
{
	$uid = (int) $_SESSION['userinfo']['uid'];
	$result = db_query("SELECT * from mail.v_virtual_mail WHERE useraccount='{$uid}'");
	$ret = array();
	while ($line = mysql_fetch_assoc($result))
	{
		array_push($ret, $line);
	}
	DEBUG($ret);
	return $ret;
}



function get_vmail_domains()
{
	$uid = (int) $_SESSION['userinfo']['uid'];
	$result = db_query("SELECT id, domainname FROM mail.v_vmail_domains WHERE useraccount='{$uid}'");
	if (mysql_num_rows($result) == 0)
		system_failure('Sie haben keine Domains für virtuelle Mail-Verarbeitung');
	$ret = array();
	while ($tmp = mysql_fetch_object($result))
		array_push($ret, $tmp);
	return $ret;
}



function domainselect($selected = NULL, $selectattribute = '')
{
  global $domainlist;
  if ($domainlist == NULL)
    $domainlist = get_vmail_domains();
  $selected = (int) $selected;

  $ret = '<select id="domain" name="domain" size="1" '.$selectattribute.' >';
  foreach ($domainlist as $dom)
  {
    $s = ($selected == $dom->id) ? ' selected="selected" ': '';
    $ret .= "<option value=\"{$dom->id}\"{$s}>{$dom->domainname}</option>\n";
  }
  $ret .= '</select>';
  return $ret;
}


function encrypt_mail_password($pw)
{
  DEBUG("unencrypted PW: ".$pw);
  require_once('inc/base.php');
  $salt = random_string(8);
  $encpw = crypt($pw, "\$1\${$salt}\$");
  DEBUG("encrypted PW: ".$encpw);
  return chop($encpw);

}



function save_vmail_account($account)
{
  $uid = (int) $_SESSION['userinfo']['uid'];
  $id = $account['id'];
  if ($id != NULL)
  {
    $id = (int) $id;
    $oldaccount = get_account_details($id);
    // Erzeugt einen system_error() wenn ID ungültig
  }
  // Ab hier ist $id sicher, entweder NULL oder eine gültige ID des aktuellen users

  $account['local'] = filter_input_username($account['local']);
  if ($account['local'] == '')
  {
    input_error('Die E-Mail-Adresse braucht eine Angabe vor dem »@«!');
    return false;
  }
  $account['domain'] = (int) $account['domain'];
  $domainlist = get_vmail_domains();
  $valid_domain = false;
  foreach ($domainlist as $dom)
  {
    if ($dom->id == $account['domain'])
    {
      $valid_domain = true;
      break;
    }
  }
  if (($account['domain'] == 0) || (! $valid_domain))
  {
    input_error('Bitte wählen Sie eine Ihrer Domains aus!');
    return false;
  }
  $type = NULL;
  switch ($account['type'])
  {
    case 'forward':
                     $account['data'] = filter_input_general($account['data']);
                     if (! check_emailaddr($account['data']))
		       system_failure('Das Weiterleitungs-Ziel ist keine E-Mail-Adresse!');
		     $type = 'forward';
                     break;
    case 'mailbox':
                     $account['data'] = stripslashes($account['data']);
                     if ($account['data'] != '')
                     {
                       $crack = strong_password($account['data']);
                       if ($crack !== true)
                       {
                         input_error('Ihr Passwort ist zu einfach. bitte wählen Sie ein sicheres Passwort!'."\nDie Fehlermeldung lautet: »{$crack}«");
                         return false;
                       }
                       $account['data'] = encrypt_mail_password($account['data']);
                     }
                     $type = 'mailbox';
                     break;
  }
  if ($type == NULL)
  {
    input_error('Problem mit der »type«-Variable!');
    return false;
  }

  $spam = 'NULL';
  switch ($account['spamfilter'])
  {
    case 'folder':
      if ($type == 'forward')
      {
        input_error('Sie können nicht in einen IMAP-Unterordner zustellen lassen, wenn Sie gar kein IMAP-Konto anlegen!');
	return false;
      }
      $spam = "'folder'";
      break;
    case 'tag':
      $spam = "'tag'";
      break;
    case 'delete':
      $spam = "'delete'";
      break;
  }

  $virus = 'NULL';
  switch ($account['virusfilter'])
  {
    case 'folder':
      if ($type == 'forward')
      {
        input_error('Sie können nicht in einen IMAP-Unterordner zustellen lassen, wenn Sie gar kein IMAP-Konto anlegen!');
	return false;
      }
      $virus = "'folder'";
      break;
    case 'tag':
      $virus = "'tag'";
      break;
    case 'delete':
      $virus = "'delete'";
      break;
  }

  $account['local'] = mysql_real_escape_string($account['local']);
  $account['data'] = mysql_real_escape_string($account['data']);
  $account['spamexpire'] = (int) $account['spamexpire'];
  $account['virusexpire'] = (int) $account['virusexpire'];

  $query = '';
  if ($id == NULL)
  {
    $query = "INSERT INTO mail.virtual_mail (local, domain, type, data, spamfilter, virusfilter, spamexpire, virusexpire) VALUES ";
    $query .= "('{$account['local']}', {$account['domain']}, '{$type}', '{$account['data']}', {$spam}, {$virus}, {$account['spamexpire']}, {$account['virusexpire']});";
  }
  else
  {
    $password = ", data='{$account['data']}'";
    if ($account['data'] == '')
      $password = '';
    $query = "UPDATE mail.virtual_mail SET local='{$account['local']}', domain={$account['domain']}, type='{$type}'{$password}, ";
    $query .= "spamfilter={$spam}, virusfilter={$virus}, spamexpire={$account['spamexpire']}, virusexpire={$account['virusexpire']} ";
    $query .= "WHERE id={$id} LIMIT 1;";
  }
  db_query($query); 
}



function delete_account($id)
{
  $account = get_account_details($id);
  db_query("DELETE FROM mail.virtual_mail WHERE id={$account['id']};");
}


?>

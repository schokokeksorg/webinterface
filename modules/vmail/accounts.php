<?php

require_once('inc/base.php');
require_once('inc/security.php');
require_role(ROLE_SYSTEMUSER);

require_once('vmail.php');

$domains = get_vmail_domains();
$all_accounts = get_vmail_accounts();

$sorted_by_domains = array();
foreach ($all_accounts AS $account)
{
  if (array_key_exists($account['domain'], $sorted_by_domains))
    array_push($sorted_by_domains[$account['domain']], $account);
  else
    $sorted_by_domains[$account['domain']] = array($account);
}

DEBUG($sorted_by_domains);

output('<h3>E-Mail-Accounts</h3>
<p>Folgende E-Mail-Konten sind eingerichtet:</p>
');
        foreach ($sorted_by_domains as $accounts_on_domain)
        {
	    output('<h4>'.$accounts_on_domain[0]['domainname'].'</h4>');
	    foreach ($accounts_on_domain AS $this_account)
	    {
	      $acc = get_account_details($this_account['id']);
	      $actions = array();
	      DEBUG($acc);
	      if ($acc['password'] != '')
	      {
	      	$spam = 'ohne Spamfilter';
	        switch ($acc['spamfilter'])
	  	{
		        case 'folder':  $spam = 'Spam in Unterordner';
					break;
			case 'tag':	$spam = 'Spam markieren';
					break;
			case 'delete':	$spam = 'Spam nicht zustellen';
					break;
	        }
	        array_push($actions, "Ablegen in Mailbox ({$spam})");
	      }
	      foreach ($acc['forwards'] AS $fwd)
	      {
	      	$spam = 'ohne Spamfilter';
	        switch ($fwd['spamfilter'])
	  	{
			case 'tag':	$spam = 'Spam markieren';
					break;
			case 'delete':	$spam = 'Spam nicht zustellen';
					break;
	        }
		$fwd['destination'] = filter_input_general($fwd['destination']);
	        array_push($actions, "Weiterleitung an <strong>{$fwd['destination']}</strong> ({$spam})");
	      }
	      $dest = '';
	      if (count($actions) > 0)
	      {
	        $dest = "<ul>";
		foreach ($actions as $a)
		  $dest .= "<li>{$a}</li>";
		$dest .= '</ul>';
	      }
              output('
              <div style="margin-left: 2em; margin-top: 0.5em; padding: 0.1em 0.5em;"><p>'.internal_link('edit.php', $acc['local'].'@'.$this_account['domainname'], 'id='.$acc['id']).' <a href="save.php?action=delete&amp;id='.$acc['id'].'"><img src="'.$prefix.'images/delete.png" alt="löschen" title="Dieses Konto löschen"/></a></p>
	      <p>'.$dest.'</p></div>');
	    }

        }
output('<p><a href="edit.php">Neuen Account anlegen</a></p>');

/* FIXME: Das sollte nur kommen, wenn der IMAP/POP3-Menü-Eintrag nicht da ist */
output('<p>Hinweis: schokokeks.org bietet für fortgeschrittene Nutzer die manuelle Einrichtung von POP3/IMAP-Accounts.<br/><a href="'.$prefix.'go/imap/accounts.php?action=create">Neuen POP3/IMAP-Account anlegen</a></p>');



?>

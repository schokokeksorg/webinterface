<?php

require_once('inc/base.php');
require_once('inc/icons.php');
require_once('inc/security.php');
require_role(ROLE_SYSTEMUSER);

require_once('hasdomain.php');

if (! user_has_vmail_domain()) {
  title("E-Mail-Verwaltung");
  
  output('
<p>Sie können bei '.config('company_name').' die E-Mails Ihrer Domains auf zwei unterschiedliche Arten empfangen.</p>
<ol><li>Sie können einfache E-Mail-Konten erstellen, die ankommende E-Mails speichern oder weiterleiten.</li>
<li>Sie können die manuelle Verwaltung wählen, bei der Sie passende .courier-Dateien für den Empfang und
manuelle POP3/IMAP-Konten für den Abruf erstellen können.</li></ol>
<p>Diese Wahlmöglichkeit haben Sie pro Domain bzw. Subdomain. eine Mischung beider Verfahren ist nicht möglich. 
Subdomains können grundsätzlich nur durch Administratoren eingerichtet und verändert werden.</p>
<p>Sie haben bisher keine Domains, die auf Web-basierte Verwaltung von E-Mail-Adressen eingerichtet sind.</p>

<p> </p>

<p>Besuchen Sie die '.internal_link('domains', 'Domain-Einstellungen').' um diese Auswahl für Ihre Domains zu ändern.</p>

<p>Wenn Sie die manuelle Einrichtung möchten oder keine eigene Domain nutzen, können Sie unter '.internal_link('imap', 'POP3/IMAP').' manuelle POP3-/IMAP-Konten erstellen.</p>

');
}
else
{


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

title('E-Mail-Accounts');
if (count($sorted_by_domains) > 0)
{
  output('
<p>Folgende E-Mail-Konten sind eingerichtet:</p>
');
  foreach ($sorted_by_domains as $accounts_on_domain)
  {
	    output('<h4>'.$accounts_on_domain[0]['domainname'].' <small>('.other_icon('information.png', 'Zugangsdaten anzeigen').' '.internal_link('logindata', 'Zugangsdaten für E-Mail-Abruf anzeigen', 'server='.get_server_by_id($accounts_on_domain[0]['server']).'&type=vmail').')</small></h4>');

	    foreach ($accounts_on_domain AS $this_account)
	    {
	      $acc = get_account_details($this_account['id']);
	      $actions = array();
	      DEBUG($acc);
	      if ($acc['password'] != '')
	      {
                $percent = round(( $acc["quota_used"] / $acc["quota"] ) * 100 );
                $color = ( $percent > 95 ? 'red' : ($percent > 75 ? "yellow" : "green" ));
                $width = 2 * min($percent, 100);
                $quotachart = "<div style=\"margin: 2px 0; padding: 0; width: 200px; border: 1px solid black;\"><div style=\"font-size: 1px; background-color: {$color}; height: 10px; width: {$width}px; margin: 0; padding: 0;\">&#160;</div></div> {$acc['quota_used']} MB von {$acc['quota']} MB belegt";
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
	        array_push($actions, "Ablegen in Mailbox ({$spam})<br />".$quotachart);
	      }
        if ($acc['autoresponder']) {
            $now = date( 'Y-m-d H:i:s' );
            $valid_from = $acc['autoresponder']['valid_from'];
            $valid_until = $acc['autoresponder']['valid_until'];
            if ($valid_from == NULL) {
              // Autoresponder abgeschaltet
              //array_push($actions, "<strike>Automatische Antwort versenden</strike> (Abgeschaltet)");
            } elseif ($valid_from > $now) {
              array_push($actions, "<strike>Automatische Antwort versenden</strike> (Wird aktiviert am {$valid_from})");
            } elseif ($valid_until == NULL) {
              array_push($actions, "Automatische Antwort versenden (Unbefristet)");
            } elseif ($valid_until > $now) {
              array_push($actions, "Automatische Antwort versenden (Wird deaktiviert am {$valid_until})");
            } elseif ($valid_until < $now) {
              array_push($actions, "<strike>Automatische Antwort versenden</strike> (Automatisch abgeschaltet seit {$valid_until})");
            }
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
              <div style="margin-left: 2em; margin-top: 0.5em; padding: 0.1em 0.5em;"><p>'.internal_link('edit', $acc['local'].'@'.$this_account['domainname'], 'id='.$acc['id']).' '.internal_link("save", '<img src="'.$prefix.'images/delete.png" alt="löschen" title="Dieses Konto löschen"/>', "action=delete&id=".$acc['id']).'</p>
	      <p>'.$dest.'</p></div>');
	    }
  }
}
else
{
  output('<p><em>Sie haben bisher keine E-Mail-Adressen angelegt</em></p>');
}
        
addnew("edit", "Neue E-Mail-Adresse anlegen");

/* FIXME: Das sollte nur kommen, wenn der IMAP/POP3-Menü-Eintrag nicht da ist */
output('<p style="font-size: 90%;padding-top: 0.5em; border-top: 1px solid black;">Hinweis: '.config('company_name').' bietet für fortgeschrittene Nutzer die manuelle Einrichtung von POP3/IMAP-Accounts.<br/>'.internal_link("imap", "Neuen POP3/IMAP-Account anlegen", "action=create").'</p>');

}

?>

<?php

require_once('session/start.php');

require_once('class/domain.php');
require_once('jabberaccounts.php');

require_role(ROLE_CUSTOMER);

$section = 'jabber_accounts';
$title = "Domain für Jabber freischalten";
output("<h3>Eigene Domain für Jabber-Nutzung freischalten</h3>");


$domains = get_domain_list((int) $_SESSION['customerinfo']['customerno']);
DEBUG($domains);

if (! count($domains)) {
  system_failure("Sie haben gar keine eigenen Domains.");
}

$pending_domains = array();
$available_domains = array();

foreach ($domains AS $d) {
  if ($d->jabber == 0)
    $available_domains[$d->id] = $d->domainname.'.'.$d->tld;
  if ($d->jabber == 2)
    $pending_domains[] = $d->fqdn;
}


$pending = '';
if (count($pending_domains) > 0) {
  $pending = '<h3>Wartend auf Freischaltung</h3>
<p>Folgende Domains sind bereits eingetragen und werden in der kommenden Nacht im Jabber-Server registriert:</p>
<ul>';
  foreach($pending_domains AS $d)
    $pending .= '<li>'.$d.'</li>';
  $pending .= '</ul>';
}


output('<p>Sie können hier eine Ihrer eigenen Domains für Jabber-Nutzung freischalten. Da dafür ein Neustart des Jabber-Servers nötig ist, können Sie die Domain erst ab dem darauffolgenden Tag für eigene Jabber-Accounts nutzen.</p>

'.html_form('jabber_new_domain', 'save', 'action=newdomain', '
<p>Domain wählen: '.html_select('domain', $available_domains).'</p>
<input type="submit" name="submit" value="Freischalten" />

').$pending);


?>

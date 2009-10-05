<?php

require_once('inc/base.php');
require_once('inc/security.php');

require_role(ROLE_CUSTOMER);

require_once('dnsinclude.php');

$section = 'dns_dyndns';


$new = true;
$dyndns = array();
if (isset($_REQUEST['id']))
{
  $dyndns = get_dyndns_account($_REQUEST['id']);
  $new = false;
}


$username_http = $_SESSION['userinfo']['username'];
if (isset($dyndns['handle']))
  $username_http .= "_{$dyndns['handle']}";

$http_update_info = '';
if (isset($dyndns['password']))
  $http_update_info = ' Lassen Sie das Passworteingabefeld unberührt um das bestehende Passwort zu erhalten. Entfernen Sie das bestehende Passwort um das HTTP-Update zu deaktivieren.';


$output .= '<script type="text/javascript">
var username = "'.$_SESSION['userinfo']['username'].'";
var handle;
var http_username;

function updateUsernameHTTP() {
handle = document.getElementById("handle").value;
http_username = username;
if (handle != "")
http_username = username + "_" + handle;

document.getElementById("username_http").firstChild.data = http_username;
}

</script>
';


$output .= '<h3>DynDNS-Account</h3>';


if (! $new ) 
  $output .= '<div style="padding: 0.5em; border: 1px solid black;"><strong>aktuelle Daten:</strong><br />
  letztes Update: '.$dyndns['lastchange'].'<br />
  aktuelle Adresse: '.$dyndns['address'].'
  </div>';

$form = '<p><label for="handle">Bezeichnung:</label>&#160;<input type="text" name="handle" id="handle" value="'.(isset($dyndns['handle']) ? $dyndns['handle'] : '').'" onkeyup="updateUsernameHTTP()" /></p>

<h4>Update per HTTPs</h4>
<p style="margin-left: 2em;">Geben Sie hier ein Passwort ein um das Update per HTTPs zu aktivieren.'.$http_update_info.'</p>
<p style="margin-left: 2em;">Benutzername:&#160;<strong><span id="username_http">'.$username_http.'</span></strong></p>
<p style="margin-left: 2em;"><label for="password_http">Passwort:</label>&#160;<input type="password" id="password_http" name="password_http" value="'.(isset($dyndns['password']) ? '************' : '').'" /></p>

<h4>Update per SSH</h4>
<p style="margin-left: 2em;">Kopieren Sie Ihren SSH-public-Key im OpenSSH-Format in dieses Eingabefeld um das Update per SSH zu aktivieren.</p>
<p style="margin-left: 2em; vertical-align: middle;"><label for="sshkey">SSH Public-Key:</label><br /><textarea style="height: 10em; width: 80%;" id="sshkey" name="sshkey">'.(isset($dyndns['sshkey']) ? $dyndns['sshkey'] : '').'</textarea></p>

<p style="margin-left: 2em;"><input type="submit" value="Speichern" /></p>
';


$output .= html_form('dyndns_edit', 'save', 'type=dyndns&action=edit&'.(isset($_REQUEST['id']) ? 'id='.$_REQUEST['id'] : ''), $form);

  
if (! $new )
{
  $records = get_dyndns_records($_REQUEST['id']);

  $output .= '<h4>Folgende DNS-records sind mit diesem DynDNS-Account verknüpft:</h4>
  
  <ul>';
  
  foreach ($records AS $record) {
    $output .= '<li>'.$record['fqdn'].' (Typ: '.strtoupper($record['type']).' / TTL: '.$record['ttl'].' Sek.)</li>';
  }
  
  $output .= '</ul>';
}


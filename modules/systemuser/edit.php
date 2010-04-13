<?php

require_once('inc/base.php');

require_once('useraccounts.php');

require_role(array(ROLE_CUSTOMER, ROLE_SYSTEMUSER));


$title = "System-Benutzeraccounts";
$section = "systemuser_accounts";

$account = NULL;
$role = $_SESSION['role'];
if ($role & ROLE_CUSTOMER)
{
  $account = get_account_details($_GET['uid']);
}
else
{
  $account = get_account_details($_SESSION['userinfo']['uid'], $_SESSION['userinfo']['customerno']);
}


output("<h3>Bearbeiten von Benutzer »{$account['username']}«</h3>");

#if (customer_useraccount($account['uid']))
#  system_failure('Aus Sicherheitsgründen können Sie diesen Account nicht ändern!');

$shells = available_shells();
$defaultname = ($account['name'] ? '' : 'checked="checked" ');
$nondefaultname = ($account['name'] ? 'checked="checked" ' : '');

$customerquota = get_customer_quota();

$maxquota = $customerquota['max'] - $customerquota['assigned'] + $account['quota'];


$form = '

<h5>Name (E-Mail-Absender, ...)</h5>
<div style="margin-left: 2em;"> 
  <p><input type="radio" name="defaultname" id="defaultname" value="1" '.$defaultname.'/> <label for="defaultname">Kundenname: <strong>'.$_SESSION['customerinfo']['name'].'</strong></label></p>
  <p><input type="radio" name="defaultname" id="nondefaultname" value="0" '.$nondefaultname.'/> <label for="nondefaultname">Abweichend:</label> <input type="text" name="fullname" id="fullname" value="'.$account['name'].'" /></p>
</div>
';

if ($role & ROLE_CUSTOMER)
{
  $form .= '
<h5>Speicherplatz</h5>
<div style="margin-left: 2em;">
  <p>Wenn Sie mehrere Benutzeraccounts haben, können Sie den verfügbaren Speicherplatz selbst auf diese Accounts verteilen, bis diese zusammen das Limit erreichen, das für Ihr Kundenkonto vereinbart wurde (aktuell insgesamt '.$customerquota['max'].' MB).</p>
  <p><label for="quota">Speicherplatz für »<strong>'.$account['username'].'</strong>«:</label> <input style="text-align: right; width: 5em;" type="text" name="quota" id="quota" value="'.$account['quota'].'" /> MB (Maximal '.$maxquota.' MB möglich.)</p>
</div>
';
}

$form .= '
<h5>Shell</h5>
<div style="margin-left: 2em;">
  <p>Hier können Sie eine andere Kommandozeile einstellen. Tun Sie das bitte nur, wenn Sie wissen was Sie tun. Möchten Sie gerne eine Shell benutzen, die hier nicht aufgeführt ist, wenden Sie sich bitte an den Support.</p>
  <p>'.html_select('shell', $shells, $account['shell']).'</p>
</div>

<p>
<input type="submit" name="submit" value="Speichern" />
</p>
';

output(html_form('systemuser_edit', 'save', 'action=edit&uid='.$account['uid'], $form));


?>

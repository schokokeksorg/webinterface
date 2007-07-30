<?php

require_once('inc/debug.php');

require_once('session/start.php');
require_once('su.php');

require_role(ROLE_SYSADMIN);

if (isset($_POST['submit']))
{
  check_form_token('su_su');
  $id = (int) $_POST['destination'];
  $role = find_role($id, '', True);
  setup_session($role, $id);

  header('Location: ../../go/index/index.php');
  die();
}



$title = "Benutzer wechseln";

output('<h3>Benutzer wechseln</h3>
<p>Hiermit können Sie (als Admin) das Webinterface mit den Rechten eines beliebigen anderen Benutzers benutzen.</p>
<p>Benutzer auswählen: ');

$users = list_system_users();
$options = '';
foreach ($users as $user)
{
  $options .= "  <option value=\"{$user->uid}\">{$user->username} ({$user->uid})</option>\n";
}

output(html_form('su_su', 'su.php', '', '<select name="destination" size="1">
'.$options.'
</select>
<input type="submit" name="submit" value="zum Benutzer wechseln" />
'));
output('<p>Kunde auswählen: ');

$customers = list_customers();
$options = '';
foreach ($customers as $customer)
{
  $options .= "  <option value=\"{$customer->id}\">{$customer->id} - {$customer->name}</option>\n";
}

output(html_form('su_su', 'su.php', '', '<select name="destination" size="1">
'.$options.'
</select>
<input type="submit" name="submit" value="zum Kunden wechseln" />
'));
output("<br />");



?>

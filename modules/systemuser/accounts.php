<?php

require_once('session/start.php');

require_once('useraccounts.php');

require_role(ROLE_CUSTOMER);

$title = "System-Benutzeraccounts";


output("<h3>System-Benutzeraccounts</h3>");

if (! customer_may_have_useraccounts())
{
  warning("Sie haben bisher keine Benutzeraccounts. Der erste (»Stamm-«)Account muss von einem Administrator angelegt werden.");
}
else
{
  $accounts = list_useraccounts();
  output("<p>Folgende Benutzeraccounts haben Sie bisher:</p>");
  output("<table><tr><th>Benutzername</th><th>Name</th><th>Erstellt am</th><th>Speicherplatz</th></tr>");
  foreach ($accounts as $acc)
  {

    output("<tr><td>");
    if (customer_useraccount($acc->uid))
      output($acc->username);
    else
      output(internal_link('edit.php', $acc->username, "uid={$acc->uid}"));
    output("</td><td>{$acc->name}</td><td>{$acc->erstellungsdatum}</td><td>{$acc->softquota} MB</td></tr>");
  }
  output("</table><br />");
}


?>

<?php
require_once("includes/newsletter.php");
require_once("inc/base.php");
require_role(ROLE_CUSTOMER);

if (! isset($_REQUEST['id'])) {
  system_failure("Keine ID!");
}

$item = get_news_item($_REQUEST['id']);

$section = 'newsletter_newsletter';
title("Newsletter vom ".$item ['date']);

output("<h4>".$item['subject']."</h4>");

output("<p>".internal_link("newsletter", "<em>Zurück zur Übersicht</em>")."</p>");

output("<pre style=\"margin-left: 2em;\">".$item['content']."</pre>");

output("<p>".internal_link("newsletter", "<em>Zurück zur Übersicht</em>")."</p>");


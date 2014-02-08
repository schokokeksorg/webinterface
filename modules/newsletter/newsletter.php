<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2014 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once("includes/newsletter.php");
require_once("inc/base.php");
require_role(ROLE_CUSTOMER);

title("Newsletter");

output("<p>Mit unserem Newsletter informieren wir Sie unregelmäßig (typischer Weise maximal einmal im Monat) über Änderungen und neue Möglichkeiten bei schokokeks.org. Es handelt sich in der Regel um techniche Änderungen, geplante Wartungsarbeiten oder neue Möglichkeiten, die Ihren Benutzeraccount bei schokokeks.org betreffen.</p>");
//output("<p>Mehrere Adressen trennen Sie bitte durch Kommata voneinander.</p>");

$oldaddr = get_newsletter_address();

$yes = ' checked="checked" ';
$no = '';
if (! $oldaddr) {
  $yes = '';
  $no = ' checked="checked" ';
}

$form = '<p><input type="radio" id="newsletter_yes" name="newsletter" value="yes" '.$yes.' /> <label for="newsletter_yes">Newsletter soll gesendet werden an:</label> <input type="text" name="recipient" id="recipient" value="'.filter_input_general($oldaddr).'" maxlength="255" /></p>
<p><input type="radio" id="newsletter_no" name="newsletter" value="no" '.$no.' /> <label for="newsletter_no">Ich möchte gar keinen Newsletter erhalten.</label></p>

<p><input type="submit" value="Speichern" /></p>';



output(html_form("newsletter", "save.php", "", $form));

output("<h3>Vergangene Newsletter</h3>
<p>Hier sehen Sie die Newsletter des vergangenen Jahres zum Nachlesen.</p>");

output("<ul>");
$news = get_latest_news();
foreach ($news as $item) {
  output("<li>".internal_link("read", $item['date'].': '.$item['subject'], "id=".$item['id'])."</li>");
}
output("</ul>");




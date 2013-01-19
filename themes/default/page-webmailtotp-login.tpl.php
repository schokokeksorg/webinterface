<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2013 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/
?><?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de">
<head>

<?php 
if ($title)
	echo "<title>$title - Administration</title>";
else
	echo "<title>Administration</title>";
?>
<link rel="stylesheet" href="<?php echo $THEME_PATH; ?>style.css" type="text/css" media="screen" title="Normal" />
<link rel="shortcut icon" href="<?php echo $THEME_PATH; ?>favicon.ico" type="image/x-icon" />
<?php echo $html_header; ?>
</head>

<body onload="javascript:document.getElementById('code').focus();">
<div><a href="#content" style="display: none;">Zum Inhalt</a></div>

<div class="menu">
<a href="<?php echo $BASE_PATH; ?>"><img src="<?php echo $THEME_PATH; ?>images/schokokeks.png" width="190" height="141" alt="schokokeks.org Hosting" /></a>

<?php echo $menu; ?>

<?php echo $userinfo; ?>

</div>

<div class="content">
<a id="content" style="display: none"> </a>

<?php
if ($messages) {
  echo $messages;
}
?>

<h3>Sicherheits-Code</h3>
<p>Ihr Zugang ist mit Zwei-Faktor-Anmeldung geschützt. Sie müssen daher jetzt noch den aktuellsten Code Ihres TOTP-Geräts eingeben.</p>
<form action="" method="post">
<p><label for="code" class="login_label">Google-Authenticator-Code:</label> <input type="text" id="code" name="webinterface_totpcode" size="20" /></p>
<p><span class="login_label">&#160;</span> <input type="submit" value="Prüfen" /></p>
</form>

</div>

<div class="foot">
<p>Sollten Sie auf dieser Administrations-Oberfläche ein Problem entdecken oder Hilfe benötigen, schreiben Sie bitte eine einfache eMail an <a href="mailto:root@schokokeks.org">root@schokokeks.org</a>. Unser <a href="http://www.schokokeks.org/kontakt">Impressum</a> finden Sie auf der <a href="http://www.schokokeks.org/">öffentlichen Seite</a>. Lizenzinformationen zu diesem Webinterface und verwendeten Rechten finden Sie <a href="../../images/about.php">indem Sie hier klicken</a>.</p>

</div>


</body>
</html>

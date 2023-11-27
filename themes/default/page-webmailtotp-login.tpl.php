<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/
?><!DOCTYPE html>
<html lang="de">
<head>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<?php
if ($title) {
    echo "<title>$title - Administration</title>";
} else {
    echo "<title>Administration</title>";
}
?>
<link rel="stylesheet" href="<?php echo $THEME_PATH; ?>style.css" type="text/css" media="screen" title="Normal" />
<link rel="shortcut icon" href="<?php echo $THEME_PATH; ?>favicon.ico" type="image/x-icon" />
<?php echo $html_header; ?>
</head>

<body onload="javascript:document.getElementById('code').focus();">
<div><a href="#content" style="display: none;">Zum Inhalt</a></div>

<a href="javascript:void(0);" class="menuicon" id="showmenu" onclick="showMenu()"><img src="<?php echo $THEME_PATH; ?>images/bars.svg" alt=""><span id="showmenutext">Menü</span></a>
<a href="<?php echo $BASE_PATH; ?>" class="logo"><img src="<?php echo $THEME_PATH; ?>images/schokokeks.png" width="190" height="141" alt="schokokeks.org Hosting" /></a>
<div class="sidebar" id="sidebar">

<div class="menu">
<?php echo $menu; ?>
</div>
<div class="userinfo">
<?php echo $userinfo; ?>
</div>
</div>

<div class="content">
<a id="content" style="display: none"> </a>

<?php
if ($messages) {
    echo $messages;
}
?>

<h3 class="headline">Sicherheits-Code</h3>
<p>Ihr Zugang ist mit Zwei-Faktor-Anmeldung geschützt. Sie müssen daher jetzt noch den aktuellsten Code Ihres TOTP-Geräts eingeben.</p>
<form method="post">
<p><label for="code" class="login_label">Google-Authenticator-Code:</label> <input type="text" id="code" name="webinterface_totpcode" size="20" /></p>
<p><span class="login_label">&#160;</span> <input type="submit" value="Prüfen" /></p>
</form>

</div>

<div class="foot">
<p>Sollten Sie auf dieser Administrations-Oberfläche ein Problem entdecken oder Hilfe benötigen, schreiben Sie bitte eine einfache eMail an <a href="mailto:root@schokokeks.org">root@schokokeks.org</a>. Unser <a href="https://schokokeks.org/kontakt">Impressum</a> finden Sie auf der <a href="https://schokokeks.org/">öffentlichen Seite</a>. Lizenzinformationen zu diesem Webinterface und verwendeten Rechten finden Sie, <a href="<?php echo $BASE_PATH; ?>go/about/about">indem Sie hier klicken</a>.</p>

</div>


</body>
</html>

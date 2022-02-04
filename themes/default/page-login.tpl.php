<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2018 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see
https://creativecommons.org/publicdomain/zero/1.0/

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
<script src="<?php echo $THEME_PATH; ?>script.js"></script>
</head>

<body onload="javascript:document.getElementById('username').focus();">
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

<h3 class="headline">schokokeks.org Hosting Webinterface</h3>
<p>Auf dieser Seite können Sie diverse Einstellungen Ihres Accounts auf schokokeks.org Hosting festlegen. Sofern Sie noch kein Kunde von schokokeks.org Hosting sind, können Sie diese Seite nicht benutzen. Besuchen Sie in diesem Fall bitte unsere <a href="https://schokokeks.org">öffentliche Seite</a>.</p>
<form method="post">
<p class="login_field"><label for="username" class="login_label">Benutzername oder E-Mail-Adresse:</label> <input type="text" id="username" name="webinterface_username" size="30" /></p>
<p class="login_field"><label for="password" class="login_label">Passwort:</label> <input type="password" id="password" name="webinterface_password" size="30" /> &nbsp; (<a href="<?php echo $BASE_PATH; ?>go/index/lost_password">Passwort vergessen?</a>)</p>
<p><span class="login_label">&#160;</span> <input type="submit" value="Anmelden" /></p>
</form>
<p>Sie können sich hier mit Ihrem System-Benutzernamen, Ihrer E-Mail-Adresse oder Ihrer Kundennummer (jeweils mit zugehörigem Passwort) anmelden. Je nach gewählten Daten erhalten Sie unterschiedliche Zugriffsrechte.</p>
<?php /* <p>Sollten Sie Ihr Benutzer-Passwort nicht mehr kennen, wenden Sie sich bitte an den Support. Passwörter für E-Mail-Konten kann der Eigentümer des Benutzeraccounts neu setzen.</p> */ ?>

<p><em><a href="../../certlogin/?destination=go/<?php echo $go; ?>"  >Mit einem Client-Zertifikat anmelden</a></em> (<a href="../../go/index/certinfo"  >Wie geht das?</a>)</p>


<?php if ($footnotes) {
    echo '<div class="footnotes">';
    foreach ($footnotes as $num => $explaination) {
        echo '<p>'.str_repeat('*', $num+1).': '.$explaination.'</p>';
    }
    echo '</div>';
} ?>
</div>

<div class="foot">
<p>Sollten Sie auf dieser Administrations-Oberfläche ein Problem entdecken oder Hilfe benötigen, schreiben Sie bitte eine einfache eMail an <a href="mailto:root@schokokeks.org">root@schokokeks.org</a>. Unser <a href="https://schokokeks.org/kontakt">Impressum</a> finden Sie auf der <a href="https://schokokeks.org/">öffentlichen Seite</a>. Lizenzinformationen zu diesem Webinterface und verwendeten Rechten finden Sie <a href="<?php echo $BASE_PATH; ?>go/about/about">indem Sie hier klicken</a>.</p>

</div>


</body>
</html>

<?xml version="1.0" encoding="utf-8"?>
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
<link rel="stylesheet" href="<?php echo $BASE_PATH; ?>css/default.css" type="text/css" media="screen" title="Normal" />
<link rel="stylesheet" href="<?php echo $THEME_PATH; ?>style.css" type="text/css" media="screen" title="Normal" />
<link rel="shortcut icon" href="<?php echo $THEME_PATH; ?>favicon.ico" type="image/x-icon" />
<?php echo $html_header; ?>
</head>

<body>
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

<h3>schokokeks.org Hosting Webinterface</h3>
<p>Auf dieser Seite können Sie diverse Einstellungen Ihres Accounts auf schokokeks.org Hosting festlegen. Sofern Sie noch kein Kunde von schokokeks.org Hosting sind, können Sie diese Seite nicht benutzen. Besuchen Sie in diesem Fall bitte unsere <a href="http://www.schokokeks.org">öffentliche Seite</a>.</p>
<form action="" method="post">
<p><span class="login_label">Benutzer<sup>*</sup>:</span> <input type="text" name="username" size="30" /></p>
<p><span class="login_label">Passwort:</span> <input type="password" name="password" size="30" /></p>
<p><span class="login_label">&#160;</span> <input type="submit" value="Anmelden" /></p>
</form>
<p><sup>*</sup> Sie können sich hier mit Ihrem System-Benutzernamen, Ihrem IMAP-Account oder Ihrer Kundennummer (jeweils mit zugehörigem Passwort) anmelden. Je nach gewählten Daten erhalten Sie unterschiedliche Zugriffsrechte.</p>
<p>Sollten Sie Ihr Passwort nicht mehr kennen, wenden Sie sich bitte unter Angabe Ihres Benutzernamens und/oder Ihrer Kundennummer an den Support. Passwörter für E-Mail-Konten kann der Eigentümer des Benutzeraccounts neu setzen.</p>

<p><em><a href="../../certlogin?destination=go/index/index"  >Mit einem Client-Zertifikat anmelden</a></em> (<a href="../../go/index/certinfo"  >Wie geht das?</a>)</p></div>


</div>

<div class="foot">
<p>Sollten Sie auf dieser Administrations-Oberfläche ein Problem entdecken oder Hilfe benötigen, schreiben Sie bitte eine einfache eMail an <a href="mailto:root@schokokeks.org">root@schokokeks.org</a>. Unser <a href="http://www.schokokeks.org/kontakt">Impressum</a> finden Sie auf der <a href="http://www.schokokeks.org/">öffentlichen Seite</a>. Lizenzinformationen zu diesem Webinterface und verwendeten Rechten finden Sie <a href="../../images/about.php">indem Sie hier klicken</a>.</p>

</div>


</body>
</html>

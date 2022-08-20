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
<link rel="shortcut icon" href="<?php echo $THEME_PATH; ?>favicon.ico" type="image/x-icon" />
<?php echo $html_header; ?>
<link rel="stylesheet" href="<?php echo $THEME_PATH; ?>style.css" type="text/css" media="screen" title="Normal" />
<script src="<?php echo $THEME_PATH; ?>script.js"></script>
</head>

<body>
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
if ($headline) {
    echo "<h3 class=\"headline\">$headline</h3>";
}
?>

<?php
if ($messages) {
    echo $messages;
}
?>

<?php echo $content; ?>

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

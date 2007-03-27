<?php

if (! defined("TOP_INCLUDED"))
{

define("TOP_INCLUDED", true);

require_once("config.php");
global $config; 
require_once("inc/error.php");
global $prefix;

$menuitem = array();
$weighted_menuitem = array();

foreach ($config['modules'] as $module)
{
  $menu = array();
  include("modules/$module/menu.php");
  // $menu["foo"]["file"] enthält den Link
  foreach (array_keys($menu) as $key)
  {
    $menu[$key]["file"] = $prefix."go/".$module."/".$menu[$key]["file"];
    $weight = $menu[$key]["weight"];
    if (array_key_exists($weight, $weighted_menuitem))
      array_merge($weighted_menuitem[$weight], array($key => $menu[$key]));
    else
      $weighted_menuitem[$weight] = array($key => $menu[$key]);
  }
  $menuitem = array_merge($menuitem, $menu);
}

ksort($weighted_menuitem);
DEBUG(print_r($weighted_menuitem, true));



/*
$menuitem["index"]["label"] = "&Uuml;bersicht";
$menuitem["index"]["file"] = "index.php";


$menuitem["domains"]["label"] = "Domains";
$menuitem["domains"]["file"] = "domains.php";


$menuitem["mail"]["label"] = "E-Mail";
$menuitem["mail"]["file"] = "mail.php";

$menuitem["chpass"]["label"] = "Passwort &auml;ndern";
$menuitem["chpass"]["file"] = "chpass.php";


$menuitem["logout"]["label"] = "Abmelden";
$menuitem["logout"]["file"] = "logout.php";

*/


?>


<?php echo '<?xml version="1.0" encoding="utf-8"?>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de">
<head>

<?php
if ($title != "")
        echo '<title>Administration - '.$title.'</title>';
else
        echo '<title>Administration</title>';

echo '
<link rel="stylesheet" href="'.$prefix.'css/admin.css" type="text/css" media="screen" title="Normal" />'
?>

</head>
<body>


<div class="menu">
<img src="<?php echo $prefix; ?>images/schokokeks.png" width="190" height="136" alt="schokokeks.org" />

<?php

  foreach ($weighted_menuitem as $key => $menuitem)
        foreach ($menuitem as $key => $item)
        {
                if ($key == $section)
                {
                        echo '<a href="'.$item['file'].'" class="menuitem active">'.$item['label'].'</a>'."\n";
                        if (isset($submenu[$key]))
                        {
                                echo "\n";
                                foreach ($submenu[$key] as $item)
                                {
                                        if (basename($_SERVER['PHP_SELF']) == basename($item['file']))
                                                echo '<a href="'.$item['file'].'" class="submenuitem subactive">'.$item['label'].'</a>'."\n";
                                        else
                                                echo '<a href="'.$item['file'].'" class="submenuitem">'.$item['label'].'</a>'."\n";
                                }
                                echo "\n";
                        }
                }
                else
                        echo '<a href="'.$item['file'].'" class="menuitem">'.$item['label'].'</a>'."\n";

        }

?>

</div>

<div class="content">

<?php
show_messages();

}

?>





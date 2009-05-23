<?php

if (! defined("TOP_INCLUDED"))
{

define("TOP_INCLUDED", true);

require_once("inc/error.php");
global $prefix;

$menuitem = array();
$weighted_menuitem = array();

$submenu = array();

foreach (config('modules') as $module)
{
  $menu = false;
  if (file_exists("modules/{$module}/menu.php"))
    include("modules/{$module}/menu.php");
  if ($menu === false)
  {
    DEBUG("Modul {$module} hat keine Menüeinträge");
    continue;
  }
  DEBUG("<h4>$module</h4>");
  DEBUG($menu);
  // $menu["foo"]["file"] enthält den Link
  foreach (array_keys($menu) as $key)
  {
    $menu[$key]["file"] = $prefix."go/".$module."/".$menu[$key]["file"];
    $weight = $menu[$key]["weight"];
    if (isset($menu[$key]['submenu']))
    {
      if (isset($submenu[$menu[$key]['submenu']][$weight]))
        $submenu[$menu[$key]['submenu']][$weight] = array_merge($submenu[$menu[$key]['submenu']][$weight], array($key => $menu[$key]));
      else
        $submenu[$menu[$key]['submenu']][$weight] = array($key => $menu[$key]);
    }
    else
    {
      if (array_key_exists($weight, $weighted_menuitem))
        $weighted_menuitem[$weight] = array_merge($weighted_menuitem[$weight], array($key => $menu[$key]));
      else
        $weighted_menuitem[$weight] = array($key => $menu[$key]);
    }
  }
  $menuitem = array_merge($menuitem, $menu);
}

ksort($weighted_menuitem);
DEBUG($weighted_menuitem);

foreach ($submenu AS $weight => $data) {
  ksort($submenu[$weight]);
}

DEBUG($submenu);


header("Content-Type: ".config('mime_type'));
?>
<?php echo '<?xml version="1.0" encoding="utf-8"?>'."\n"; ?>
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
<link rel="stylesheet" href="'.$prefix.'css/admin.css" type="text/css" media="screen" title="Normal" />';

echo $html_header;
?>
</head>
<body>

<a href="#content" style="display: none;">Zum Inhalt</a>

<div class="menu">
<img src="<?php echo $prefix.config('logo'); ?>" width="190" height="141" alt="<?php echo config('company_name'); ?>" />


<?php
$role = $_SESSION['role'];

function array_key_exists_r($needle, $haystack)
{
    $result = array_key_exists($needle, $haystack);
    if ($result) return $result;
    foreach ($haystack as $v) {
        if (is_array($v)) {
            $result = array_key_exists_r($needle, $v);
        }
        if ($result) return $result;
    }
    return $result;
}



  foreach ($weighted_menuitem as $key => $menuitem)
        foreach ($menuitem as $key => $item)
        {
                if ($key == $section)
                        echo '<a href="'.$item['file'].'" class="menuitem active">'.$item['label'].'</a>'."\n";
                else
                        echo '<a href="'.$item['file'].'" class="menuitem">'.$item['label'].'</a>'."\n";
                if ($key == $section || (array_key_exists($key, $submenu) && array_key_exists_r($section, $submenu[$key])))
                {
                        if (isset($submenu[$key]))
                        {
                                echo "\n";
				foreach ($submenu[$key] as $weight => $mysub) 
				{
                                  foreach ($mysub as $sec => $item)
                                  {
                                          if ($sec == $section)
                                                  echo '<a href="'.$item['file'].'" class="submenuitem menuitem active">'.$item['label'].'</a>'."\n";
                                          else
                                                  echo '<a href="'.$item['file'].'" class="submenuitem menuitem">'.$item['label'].'</a>'."\n";
                                  }
				}
                                echo "\n";
                        }
		}

        }

if ($role != ROLE_ANONYMOUS) {
echo '<p class="userinfo">Angemeldet als:<br />';
  if ($role & ROLE_SYSTEMUSER) {
    echo '<strong>'.$_SESSION['userinfo']['username'].'</strong>';
    echo '<br />'.$_SESSION['userinfo']['name'];
    echo '<br />(Benutzer'.(($role & ROLE_CUSTOMER) ? ', Kunde' : '').')';
  }
  elseif ($role & ROLE_CUSTOMER) {
    echo '<strong>'.$_SESSION['customerinfo']['customerno'].'</strong>';
    echo '<br />'.$_SESSION['customerinfo']['name'];
    echo '<br />(Kunde)';
  }
  elseif ($role & (ROLE_MAILACCOUNT | ROLE_VMAIL_ACCOUNT)) {
    echo '<strong>'.$_SESSION['mailaccount'].'</strong><br />(E-Mail-Account)';
  }
  echo '</p>';
}

if (isset($_SESSION['admin_user'])) {
  echo '<p class="admininfo">';
  echo '<a href="'.$prefix.'go/su/back_to_admin">Zurück zu »'.$_SESSION['admin_user'].'«</a>';
  echo '</p>';
}
?>

</div>

<div class="content">
<a name="content" style="display: none"> </a>
<?php
show_messages();

}

?>





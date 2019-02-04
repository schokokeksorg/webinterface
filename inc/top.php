<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2018 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

if (! defined("TOP_INCLUDED")) {
    define("TOP_INCLUDED", true);

    require_once("inc/error.php");
    require_once("inc/debug.php");
    global $prefix, $section;

    $menuitem = array();
    $weighted_menuitem = array();

    $submenu = array();

    foreach (config('modules') as $module) {
        $menu = false;
        if (file_exists("modules/{$module}/menu.php")) {
            include("modules/{$module}/menu.php");
        }
        if ($menu === false) {
            #DEBUG("Modul {$module} hat keine Menüeinträge");
            continue;
        }
        // Menüeinträge spammen den debug-output zu
        //DEBUG("<h4>$module</h4>");
        //DEBUG($menu);
        // $menu["foo"]["file"] enthält den Link
        foreach (array_keys($menu) as $key) {
            $menu[$key]["file"] = $prefix."go/".$module."/".$menu[$key]["file"];
            $weight = $menu[$key]["weight"];
            if (isset($menu[$key]['submenu'])) {
                if (isset($submenu[$menu[$key]['submenu']][$weight])) {
                    $submenu[$menu[$key]['submenu']][$weight] = array_merge($submenu[$menu[$key]['submenu']][$weight], array($key => $menu[$key]));
                } else {
                    $submenu[$menu[$key]['submenu']][$weight] = array($key => $menu[$key]);
                }
            } else {
                if (array_key_exists($weight, $weighted_menuitem)) {
                    $weighted_menuitem[$weight] = array_merge($weighted_menuitem[$weight], array($key => $menu[$key]));
                } else {
                    $weighted_menuitem[$weight] = array($key => $menu[$key]);
                }
            }
        }
        $menuitem = array_merge($menuitem, $menu);
    }

    foreach ($submenu as $key => $content) {
        $found = false;
        foreach ($weighted_menuitem as $weight => $data) {
            if (array_key_exists($key, $data)) {
                DEBUG("found requested submenu ".$key);
                $found = true;
            }
        }
        if (! $found) {
            DEBUG("Submenu ".$key." requested but not present!");
            // Ein Submenü von einem nicht existierenden Hauptmenü wird angefordert
            // Menüpunkt muss als Hauptmenüpunkt geführt werden
            $weighted_menuitem = $weighted_menuitem + $content;
        }
    }

    ksort($weighted_menuitem);
    #DEBUG($weighted_menuitem);

    foreach ($submenu as $weight => $data) {
        ksort($submenu[$weight]);
    }

    #DEBUG($submenu);

    // Verbiete das Laden in jeglichem Frameset
    header("X-FRAME-OPTIONS: DENY");
    header("Content-Type: ".config('mime_type'));

    if (!isset($html_header)) {
        $html_header = '';
    }

    function array_key_exists_r($needle, $haystack)
    {
        $result = array_key_exists($needle, $haystack);
        if ($result) {
            return $result;
        }
        foreach ($haystack as $v) {
            if (is_array($v)) {
                $result = array_key_exists_r($needle, $v);
            }
            if ($result) {
                return $result;
            }
        }
        return $result;
    }


    $menu = '';

    foreach ($weighted_menuitem as $key => $menuitem) {
        foreach ($menuitem as $key => $item) {
            if ($key == $section) {
                $menu .= '<a href="'.$item['file'].'" class="menuitem active">'.$item['label'].'</a>'."\n";
            } else {
                $menu .= '<a href="'.$item['file'].'" class="menuitem">'.$item['label'].'</a>'."\n";
            }
            if (isset($submenu[$key])) {
                if ($key == $section || (array_key_exists($key, $submenu) && array_key_exists_r($section, $submenu[$key]))) {
                    $menu .= "\n";
                    foreach ($submenu[$key] as $weight => $mysub) {
                        foreach ($mysub as $sec => $item) {
                            if ($sec == $section) {
                                $menu .= '<a href="'.$item['file'].'" class="submenuitem menuitem active">'.$item['label'].'</a>'."\n";
                            } else {
                                $menu .= '<a href="'.$item['file'].'" class="submenuitem menuitem">'.$item['label'].'</a>'."\n";
                            }
                        }
                    }
                    $menu .= "\n";
                }
            }
        }
    }

    $userinfo = '';

    $role = $_SESSION['role'];
    if ($role != ROLE_ANONYMOUS) {
        $userinfo .= '<p class="userinfo">Angemeldet als:<br />';
        if ($role & ROLE_SYSTEMUSER && isset($_SESSION['subuser'])) {
            $userinfo .= '<strong>'.$_SESSION['subuser'].'</strong>';
            $userinfo .= '<br />Mitbenutzer von '.$_SESSION['userinfo']['username'];
        } elseif ($role & ROLE_SYSTEMUSER) {
            $userinfo .= '<strong>'.$_SESSION['userinfo']['username'].'</strong>';
            $userinfo .= '<br />'.$_SESSION['userinfo']['name'];
            $userinfo .= '<br />(UID '.$_SESSION['userinfo']['uid'].(($role & ROLE_CUSTOMER) ? ', Kunde '.$_SESSION['customerinfo']['customerno'] : '').')';
        } elseif ($role & ROLE_CUSTOMER) {
            $userinfo .= '<strong>'.$_SESSION['customerinfo']['name'].'</strong>';
            $userinfo .= '<br />(Kunde '.$_SESSION['customerinfo']['customerno'].')';
        } elseif ($role & (ROLE_MAILACCOUNT | ROLE_VMAIL_ACCOUNT)) {
            $userinfo .= '<strong>'.$_SESSION['mailaccount'].'</strong><br />(Postfach von Benutzer <em>'.$_SESSION['userinfo']['username'].'</em>)';
        }
        $userinfo .= '</p>';
    }

    if (isset($_SESSION['admin_user'])) {
        $userinfo .= '<p class="admininfo">';
        $userinfo .= '<a href="'.$prefix.'go/su/back_to_admin">Zurück zu »'.$_SESSION['admin_user'].'«</a>';
        $userinfo .= '</p>';
    }

    $messages = get_messages();

    $BASE_PATH = $prefix;
    $THEME_PATH = $prefix."themes/".config('theme')."/";
}

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

require_once('numbers.php');

$number = $_REQUEST['number'];
$country = $_REQUEST['country'];

$return = array();
$return['field'] = $_REQUEST['field'];

if ($number) {
    $num = format_number($number, $country);
    if ($num) {
        $return['number'] = $num;
        $return['valid'] = 1;
    } else {
        $return['number'] = null;
        $return['valid'] = 0;
    }
} else {
    $return['number'] = null;
    $return['valid'] = 0;
}

echo json_encode($return);

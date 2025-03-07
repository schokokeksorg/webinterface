<?php

/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('numbers.php');

$number = $_REQUEST['number'];
$country = $_REQUEST['country'];

$return = [];
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

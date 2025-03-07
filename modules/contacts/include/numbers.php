<?php

/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('vendor/autoload.php');

function format_number($number, $country)
{
    $phoneNumberUtil = \libphonenumber\PhoneNumberUtil::getInstance();
    try {
        $phoneNumber = $phoneNumberUtil->parse($number, $country);
    } catch (Exception $e) {
        return null;
    }
    if ($phoneNumberUtil->isValidNumber($phoneNumber)) {
        return $phoneNumberUtil->format($phoneNumber, 1);
    }
    return null;
}

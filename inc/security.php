<?php


function filter_input_general( $input )
{
        return htmlspecialchars(iconv('UTF-8', 'UTF-8', $input), ENT_QUOTES, 'UTF-8');
}


function filter_input_username( $input )
{
        return ereg_replace("[^[:alnum:]\_\.\+\-]", "", $input );
}

function filter_quotes( $input )
{
        return ereg_replace('["\'`]', '', $input );
}

function filter_shell( $input )
{
        return ereg_replace('[^-[:alnum:]\_\.\+ßäöüÄÖÜ/%§=]', '', $input );
}

function check_emailaddr( $input )
{
        return (preg_match("/^[a-z]+[a-z0-9]*[\.|\-|_]?[a-z0-9]+@([a-z0-9]*[\.|\-]?[a-z0-9]+){1,4}\.[a-z]{2,4}$/i", $input ) == 1);
}


?>

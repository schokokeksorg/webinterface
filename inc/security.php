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


?>

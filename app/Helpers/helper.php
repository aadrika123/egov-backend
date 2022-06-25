<?php

/**
 * Our Helper Functions
 */

//  Our DateFormat Output function
if (!function_exists('getFormattedDate')) {
    function getFormattedDate($date, $format)
    {
        $formattedDate = date($format, strtotime($date));
        return $formattedDate;
    }
}

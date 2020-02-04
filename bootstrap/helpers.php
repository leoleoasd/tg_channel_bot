<?php

/**
 * Format a string.
 * @param $msg
 * @param $vars
 * @return string
 */
function format($msg, $vars)
{
    $vars = (array)$vars;
    $msg = preg_replace_callback('#\{\}#', function($r){
        static $i = 0;
        return '{'.($i++).'}';
    }, $msg);
    return str_replace(
        array_map(function($k) {
            return '{'.$k.'}';
        }, array_keys($vars)),
        array_values($vars),
        $msg
    );
}

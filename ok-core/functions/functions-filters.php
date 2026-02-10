<?php
/**
 * FILE: ok-core/functions/functions-filters.php
 * OK Engine - Filter System
 */

global $ok_filters;
$ok_filters = [];

function add_ok_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
    global $ok_filters;
    
    $idx = is_object($function_to_add) ? spl_object_hash($function_to_add) : (string)$function_to_add;
    
    $ok_filters[$tag][$priority][$idx] = [
        'function' => $function_to_add,
        'accepted_args' => $accepted_args
    ];
}

function apply_ok_filters($tag, $value, ...$args) {
    global $ok_filters;

    if (!isset($ok_filters[$tag])) {
        return $value;
    }

    ksort($ok_filters[$tag]);

    foreach ($ok_filters[$tag] as $priority => $functions) {
        foreach ($functions as $filter) {
            if (is_callable($filter['function'])) {
                $current_args = array_merge([$value], $args);
                $value = call_user_func_array($filter['function'], array_slice($current_args, 0, $filter['accepted_args']));
            }
        }
    }
    return $value;
}
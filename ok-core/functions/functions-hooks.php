<?php
/**
 * FILE: ok-core/functions/functions-hooks.php
 * OK Engine - Action Hooks System
 */

global $ok_hooks;
$ok_hooks = [];

/**
 * Action-ის დამატება
 */
function add_ok_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
    global $ok_hooks;
    
    if (is_string($function_to_add)) {
        $idx = $function_to_add;
    } elseif (is_object($function_to_add)) {
        $idx = spl_object_hash($function_to_add);
    } else {
        $idx = (string)$priority;
    }

    $ok_hooks[$tag][$priority][$idx] = [
        'function' => $function_to_add,
        'accepted_args' => $accepted_args
    ];
}

/**
 * Action-ის წაშლა
 */
function remove_ok_action($tag, $function_to_remove, $priority = 10) {
    global $ok_hooks;
    if (!isset($ok_hooks[$tag][$priority])) return false;

    foreach ($ok_hooks[$tag][$priority] as $idx => $data) {
        if ($data['function'] === $function_to_remove) {
            unset($ok_hooks[$tag][$priority][$idx]);
            return true;
        }
    }
    return false;
}

/**
 * Action-ის გაშვება
 */
function do_ok_action($tag, ...$args) {
    global $ok_hooks;

    if (!isset($ok_hooks[$tag])) return;

    ksort($ok_hooks[$tag]);

    foreach ($ok_hooks[$tag] as $priority => $functions) {
        foreach ($functions as $action) {
            if (is_callable($action['function'])) {
                call_user_func_array($action['function'], array_slice($args, 0, $action['accepted_args']));
            }
        }
    }
}
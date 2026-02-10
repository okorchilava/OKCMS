<?php
declare(strict_types=1);

/**
 * OK Engine — Hook Manager
 *
 * Core Logic:
 * - Crash-safe (try/catch inside loops)
 * - Strict Types
 * - Naming: add_ok_action, do_ok_action, etc.
 *
 * @package OK_Engine
 * @version 2.0.0
 */

if (!defined('OK_LOADED')) {
    if (!headers_sent()) { http_response_code(403); }
    exit;
}

final class HookManager
{
    private static ?self $instance = null;

    private array $actions = [];
    private array $filters = [];

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct() {}
    private function __clone() {}
    public function __wakeup(): void
    {
        throw new RuntimeException('Cannot unserialize singleton');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Actions (add_ok_action / do_ok_action)
    // ─────────────────────────────────────────────────────────────────────────

    public function add_ok_action(string $tag, $callback, int $priority = 10, int $accepted_args = 99): self
    {
        $this->add_hook($this->actions, $tag, $callback, $priority, $accepted_args);
        return $this;
    }

    public function do_ok_action(string $tag, ...$args): void
    {
        if (empty($this->actions[$tag])) return;

        ksort($this->actions[$tag]);

        foreach ($this->actions[$tag] as $priority => $callbacks) {
            foreach ($callbacks as $item) {
                $cb = $item['cb'] ?? null;
                if (!is_callable($cb)) continue;

                $accepted = (int)($item['accepted_args'] ?? 99);
                if ($accepted < 0) $accepted = 0;

                $callArgs = array_slice($args, 0, $accepted);

                try {
                    call_user_func_array($cb, $callArgs);
                } catch (Throwable $e) {
                    $this->log_error('do_ok_action', $tag, (int)$priority, $e);
                }
            }
        }
    }

    public function remove_ok_action(string $tag, $callback, int $priority = 10): bool
    {
        return $this->remove_hook($this->actions, $tag, $callback, $priority);
    }

    public function has_ok_action(string $tag): bool
    {
        return !empty($this->actions[$tag]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Filters (add_ok_filter / apply_ok_filters)
    // ─────────────────────────────────────────────────────────────────────────

    public function add_ok_filter(string $tag, $callback, int $priority = 10, int $accepted_args = 99): self
    {
        $this->add_hook($this->filters, $tag, $callback, $priority, $accepted_args);
        return $this;
    }

    public function apply_ok_filters(string $tag, $value, ...$args)
    {
        if (empty($this->filters[$tag])) {
            return $value;
        }

        ksort($this->filters[$tag]);
        $filtered = $value;

        foreach ($this->filters[$tag] as $priority => $callbacks) {
            foreach ($callbacks as $item) {
                $cb = $item['cb'] ?? null;
                if (!is_callable($cb)) continue;

                $accepted = (int)($item['accepted_args'] ?? 99);
                if ($accepted < 1) $accepted = 1;

                $params   = array_merge([$filtered], $args);
                $callArgs = array_slice($params, 0, $accepted);

                try {
                    $result = call_user_func_array($cb, $callArgs);

                    // თუ ფილტრმა null დააბრუნა (return დაავიწყდათ), მონაცემი არ ფუჭდება
                    if ($result !== null) {
                        $filtered = $result;
                    } else {
                        error_log("[HookManager] Warning: Filter '{$tag}' returned NULL. Ignoring.");
                    }

                } catch (Throwable $e) {
                    $this->log_error('apply_ok_filters', $tag, (int)$priority, $e);
                }
            }
        }

        return $filtered;
    }

    public function remove_ok_filter(string $tag, $callback, int $priority = 10): bool
    {
        return $this->remove_hook($this->filters, $tag, $callback, $priority);
    }

    public function has_ok_filter(string $tag): bool
    {
        return !empty($this->filters[$tag]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function add_hook(array &$storage, string $tag, $callback, int $priority, int $accepted_args): void
    {
        $tag = trim($tag);
        if ($tag === '') return;

        if ($accepted_args < 0) $accepted_args = 0;
        if ($accepted_args > 99) $accepted_args = 99;

        $cbKey = $this->callback_key($callback);

        $storage[$tag][$priority][$cbKey] = [
            'cb'            => $callback,
            'accepted_args' => $accepted_args,
        ];
    }

    private function remove_hook(array &$storage, string $tag, $callback, int $priority): bool
    {
        if (empty($storage[$tag][$priority])) return false;
        $cbKey = $this->callback_key($callback);

        if (!isset($storage[$tag][$priority][$cbKey])) {
            return false;
        }

        unset($storage[$tag][$priority][$cbKey]);
        if (empty($storage[$tag][$priority])) unset($storage[$tag][$priority]);
        if (empty($storage[$tag])) unset($storage[$tag]);

        return true;
    }

    private function callback_key($callback): string
    {
        if (is_string($callback)) return 'func:' . $callback;

        if (is_array($callback) && count($callback) === 2) {
            $objOrClass = $callback[0];
            $method = (string)$callback[1];
            if (is_object($objOrClass)) {
                return 'obj:' . spl_object_hash($objOrClass) . '::' . $method;
            }
            return 'cls:' . (string)$objOrClass . '::' . $method;
        }

        if ($callback instanceof Closure) return 'closure:' . spl_object_hash($callback);
        if (is_object($callback)) return 'invokable:' . spl_object_hash($callback);

        return 'cb:' . md5(@serialize($callback) ?: 'unknown');
    }

    private function log_error(string $type, string $tag, int $prio, Throwable $e): void
    {
        error_log("[HookManager] CRASH PREVENTED in {$type} ('{$tag}', prio: {$prio}): " . $e->getMessage());
    }
}
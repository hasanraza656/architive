<?php

namespace App\Support;

/** Reads config/permissions.php: the list of every permission key and helpers for the role editor. */
class Permissions
{
    /** @return array<string, array> module => definition */
    public static function modules(): array
    {
        return config('permissions.modules', []);
    }

    /** @return array<int, string> every key such as "orders.view" */
    public static function keys(): array
    {
        $keys = [];
        foreach (self::modules() as $module => $def) {
            foreach (array_keys($def['actions']) as $action) {
                $keys[] = "$module.$action";
            }
        }

        return $keys;
    }

    public static function exists(string $key): bool
    {
        return in_array($key, self::keys(), true);
    }

    /** Drops anything that is not a real permission (protects against tampered forms and removed modules). */
    public static function clean(array $keys): array
    {
        return array_values(array_unique(array_filter($keys, fn ($k) => is_string($k) && self::exists($k))));
    }

    public static function label(string $key): string
    {
        [$module, $action] = array_pad(explode('.', $key, 2), 2, '');

        return self::modules()[$module]['actions'][$action] ?? $key;
    }

    public static function presets(): array
    {
        return config('permissions.presets', []);
    }
}

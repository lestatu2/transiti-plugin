<?php

declare(strict_types=1);

namespace Fabermind\Transiti\Admin;

final class Admin
{
    public static function boot(): void
    {
        if (! is_admin()) {
            return;
        }

        add_action('admin_enqueue_scripts', [self::class, 'enqueueAssets']);
    }

    public static function enqueueAssets(): void
    {
        // Enqueue admin assets here when needed.
    }
}

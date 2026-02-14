<?php

declare(strict_types=1);

namespace Fabermind\Transiti\Core;

use Fabermind\Transiti\Admin\Admin;

final class Plugin
{
    public static function boot(): void
    {
        add_action('plugins_loaded', [self::class, 'onPluginsLoaded']);
    }

    public static function activate(): void
    {
        // Future activation routines go here.
    }

    public static function deactivate(): void
    {
        // Future deactivation routines go here.
    }

    public static function onPluginsLoaded(): void
    {
        self::loadTextDomain();
        Admin::boot();
    }

    private static function loadTextDomain(): void
    {
        load_plugin_textdomain('transiti', false, dirname(TRANSITI_PLUGIN_BASENAME) . '/languages');
    }
}

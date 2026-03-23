<?php

declare(strict_types=1);

namespace Fabermind\Transiti\Admin;

use Fabermind\Transiti\Core\Plugin;

final class Admin
{
    public static function boot(): void
    {
        if (! is_admin()) {
            return;
        }

        add_action('admin_enqueue_scripts', [self::class, 'enqueueAssets']);
    }

    public static function enqueueAssets(string $hookSuffix): void
    {
        if (! in_array($hookSuffix, array('post.php', 'post-new.php'), true)) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (! $screen instanceof \WP_Screen || $screen->post_type !== 'rubrica') {
            return;
        }

        wp_enqueue_style(
            'transiti-admin',
            TRANSITI_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            TRANSITI_VERSION
        );

        wp_enqueue_script(
            'transiti-admin',
            TRANSITI_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            TRANSITI_VERSION,
            true
        );

        wp_localize_script(
            'transiti-admin',
            'transitiAdmin',
            array(
                'rubricaMaxCombinedLength' => Plugin::getRubricaMaxCombinedLength(),
                'rubricaCharactersLabel' => __('Caratteri', 'transiti'),
            )
        );
    }
}

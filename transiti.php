<?php
/**
 * Plugin Name: Transiti
 * Plugin URI:  http://fabermind.it
 * Description: Base plugin for custom Transiti blog features.
 * Version:     0.1.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author:      Fabermind srl
 * Author URI:  http://fabermind.it
 * Text Domain: transiti
 * Domain Path: /languages
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('TRANSITI_VERSION', '0.1.0');
define('TRANSITI_PLUGIN_FILE', __FILE__);
define('TRANSITI_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('TRANSITI_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('TRANSITI_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once TRANSITI_PLUGIN_PATH . 'src/Autoloader.php';
require_once TRANSITI_PLUGIN_PATH . 'acf.php';

\Fabermind\Transiti\Autoloader::register();

register_activation_hook(TRANSITI_PLUGIN_FILE, [\Fabermind\Transiti\Core\Plugin::class, 'activate']);
register_deactivation_hook(TRANSITI_PLUGIN_FILE, [\Fabermind\Transiti\Core\Plugin::class, 'deactivate']);

\Fabermind\Transiti\Core\Plugin::boot();


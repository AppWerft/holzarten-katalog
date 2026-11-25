<?php

/**
 * Plugin Name: Tree Species Catalog
 * Description: Extends woocommerce with a catalog of woods and harvesting location.
 * Version: 0.1.32
 * Author: Appwerft
 * Requires Plugins: woocommerce
 * Text Domain: holzarten-katalog
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

define('HOLZKATALOG_VERSION', '0.1.32');
define('HOLZKATALOG_PLUGIN_FILE', __FILE__);
define('HOLZKATALOG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HOLZKATALOG_PLUGIN_URL', plugin_dir_url(__FILE__));


// --------------------------------------------------
// BEGIN GITHUB UPDATE
// --------------------------------------------------

require_once HOLZKATALOG_PLUGIN_DIR
    . 'plugin-update-checker/plugin-update-checker.php';

$holzkatalog_update_checker =
    YahnisElsts\PluginUpdateChecker\v6\PucFactory::buildUpdateChecker(
        'https://github.com/AppWerft/holzarten-katalog/',
        HOLZKATALOG_PLUGIN_FILE,
        'holzarten-katalog'
    );

$holzkatalog_update_checker->setBranch('main');

// GitHub Release-Assets verwenden
$holzkatalog_update_checker->getVcsApi()->enableReleaseAssets(
    '/\.zip($|[?&#])/i'
);

// --------------------------------------------------
// END GITHUB UPDATE
// --------------------------------------------------

// Klassen laden
require_once HOLZKATALOG_PLUGIN_DIR . 'includes/class-holzkatalog-constants.php';
require_once HOLZKATALOG_PLUGIN_DIR . 'includes/class-holzkatalog-storage.php';
require_once HOLZKATALOG_PLUGIN_DIR . 'includes/class-holzkatalog-catalog.php';
require_once HOLZKATALOG_PLUGIN_DIR . 'includes/class-holzkatalog-woocommerce.php';
require_once HOLZKATALOG_PLUGIN_DIR . 'includes/class-holzkatalog-settings.php';
require_once HOLZKATALOG_PLUGIN_DIR . 'includes/class-holzkatalog-frontend.php';
require_once HOLZKATALOG_PLUGIN_DIR . 'includes/woocommerce/class-holzkatalog-wc-origin.php';
require_once HOLZKATALOG_PLUGIN_DIR . 'includes/class-holzkatalog-assets.php';

// Admin-Klassen
require_once HOLZKATALOG_PLUGIN_DIR . 'includes/admin/class-holzkatalog-admins.php';
require_once HOLZKATALOG_PLUGIN_DIR . 'includes/admin/class-holzkatalog-actions.php';


// Zentrale Plugin-Klasse zuletzt
require_once HOLZKATALOG_PLUGIN_DIR . 'includes/class-holzkatalog-plugin.php';
// --------------------------------------------------
// Aktivierung
// --------------------------------------------------

register_activation_hook(HOLZKATALOG_PLUGIN_FILE, function () {
    HOLZKATALOG_Storage::activate();
});


// --------------------------------------------------
// Klassen initialisieren
// --------------------------------------------------

HOLZKATALOG_Plugin::instance();
// --------------------------------------------------
// Einstellungen-Link in der Pluginübersicht
// --------------------------------------------------

add_filter(
    'plugin_action_links_' . plugin_basename(__FILE__),
    function ($links) {
      $settings_url = admin_url(
            'admin.php?page=holzarten-katalog'
        );

        array_unshift(
            $links,
            '<a href="' . esc_url($settings_url) . '">'
                . 'Einstellungen</a>'
        );

        return $links;
    }
);
HOLZKATALOG_Assets::init();
HOLZKATALOG_Settings::init();

define('HOLZKATALOG_URL', plugin_dir_url(__FILE__));

add_action('woocommerce_loaded', function () {
    HOLZKATALOG_WooCommerce::instance();
    HOLZKATALOG_WC_Origin::instance();
});

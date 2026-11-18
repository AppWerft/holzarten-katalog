<?php

/**
 * Plugin Name: Tree Species Catalog
 * Description: Extends woocommerce with a catalog of woods and harvesting location.
 * Version: 0.1.4
 * Author: Appwerft
 * Text Domain: tree-species-catalog
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

define('HK_VERSION', '0.1.4');
define('HK_PLUGIN_FILE', __FILE__);
define('HK_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HK_PLUGIN_URL', plugin_dir_url(__FILE__));


// --------------------------------------------------
// BEGIN GITHUB UPDATE
// --------------------------------------------------

require_once HK_PLUGIN_DIR
    . 'plugin-update-checker/plugin-update-checker.php';

$hk_update_checker =
    YahnisElsts\PluginUpdateChecker\v6\PucFactory::buildUpdateChecker(
        'https://github.com/AppWerft/holzarten-katalog/',
        HK_PLUGIN_FILE,
        'holzarten-katalog'
    );

$hk_update_checker->setBranch('main');

// GitHub Release-Assets verwenden
$hk_update_checker->getVcsApi()->enableReleaseAssets(
    '/\.zip($|[?&#])/i'
);

// --------------------------------------------------
// END GITHUB UPDATE
// --------------------------------------------------

// Klassen laden
require_once HK_PLUGIN_DIR . 'includes/class-hk-storage.php';
require_once HK_PLUGIN_DIR . 'includes/class-hk-catalog.php';
require_once HK_PLUGIN_DIR . 'includes/class-hk-woocommerce.php';
require_once HK_PLUGIN_DIR . 'includes/class-hk-settings.php';
require_once HK_PLUGIN_DIR . 'includes/class-hk-frontend.php';
require_once HK_PLUGIN_DIR . 'includes/class-hk-plugin.php';
require_once HK_PLUGIN_DIR . 'includes/woocommerce/class-hk-wc-origin.php';
require_once HK_PLUGIN_DIR . 'includes/class-hk-assets.php';


// --------------------------------------------------
// Aktivierung
// --------------------------------------------------

register_activation_hook(HK_PLUGIN_FILE, function () {

    // WooCommerce muss installiert und aktiv sein.
    if (!class_exists('WooCommerce')) {
        wp_die(
            'Das Plugin <strong>Holzarten-Katalog</strong> benötigt '
                . 'das installierte und aktivierte Plugin '
                . '<strong>WooCommerce</strong>.<br><br>'
                . '<a href="' . esc_url(admin_url('plugins.php')) . '">'
                . 'Zur Plugin-Verwaltung</a>',
            'WooCommerce erforderlich',
            ['back_link' => true]
        );
    }

    // Initialisierung der Plugin-Daten
    HK_Storage::activate();
});


// --------------------------------------------------
// Admin-Warnung bei deaktiviertem WooCommerce
// --------------------------------------------------

add_action('admin_notices', function () {

    if (!class_exists('WooCommerce')) {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>Holzarten-Katalog:</strong> ';
        echo 'WooCommerce ist nicht aktiv. ';
        echo 'Bitte aktiviere WooCommerce, damit alle Funktionen '
            . 'des Holzarten-Katalogs verfügbar sind.';
        echo '</p></div>';
    }
});


// --------------------------------------------------
// Klassen initialisieren
// --------------------------------------------------

HK_Plugin::instance();
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
HK_Assets::init();
HK_Settings::init();

// WooCommerce-spezifische Klassen nur bei aktivem WooCommerce
if (class_exists('WooCommerce')) {
    HK_WooCommerce::instance();
    HK_WC_Origin::instance();
}

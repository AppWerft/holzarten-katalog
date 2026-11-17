<?php

/**
 * Plugin Name: Holzarten-Katalog
 * Description: Verwaltung und Darstellung eines Holzartenkatalogs mit Suche, Filtern und Bildern.
 * Version: 0.1.0
 * Author: Appwerft
 * Text Domain: holzarten-katalog
 */

if (!defined('ABSPATH')) {
    exit;
}

define('HK_VERSION', '0.1.0');
define('HK_PLUGIN_FILE', __FILE__);
define('HK_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HK_PLUGIN_URL', plugin_dir_url(__FILE__));

// Klassen laden
require_once HK_PLUGIN_DIR . 'includes/class-hk-storage.php';
require_once HK_PLUGIN_DIR . 'includes/class-hk-catalog.php';
require_once HK_PLUGIN_DIR . 'includes/class-hk-woocommerce.php';
require_once HK_PLUGIN_DIR . 'includes/class-hk-settings.php';
require_once HK_PLUGIN_DIR . 'includes/class-hk-frontend.php';
require_once HK_PLUGIN_DIR . 'includes/class-hk-plugin.php';
require_once HK_PLUGIN_DIR . 'includes/woocommerce/class-hk-wc-origin.php';
require_once HK_PLUGIN_DIR . 'includes/class-hk-assets.php';

// Aktivierung
// Aktivierung
register_activation_hook(HK_PLUGIN_FILE, function () {

    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(HK_PLUGIN_FILE));

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

    HK_Storage::activate();
});

// Klassen initialisieren
HK_Plugin::instance();
HK_WooCommerce::instance();
HK_WC_Origin::instance();
HK_Assets::init();
HK_Settings::init();

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
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
require_once HK_PLUGIN_DIR . 'includes/class-hk-plugin.php';
require_once HK_PLUGIN_DIR . 'includes/woocommerce/class-hk-wc-origin.php';
require_once HK_PLUGIN_DIR . 'includes/class-hk-assets.php';

// Aktivierung
register_activation_hook(
    HK_PLUGIN_FILE,
    ['HK_Storage', 'activate']
);

// Klassen initialisieren
HK_Plugin::instance();
HK_WooCommerce::instance();
HK_WC_Origin::instance();
error_log('HK_ASSETS DATEI: ' . __FILE__);  
HK_Assets::init();

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

/**
 * Diagnosefunktion:
 * Fängt unerwartete Ausgaben eines einzelnen Aufrufs ab
 * und schreibt sie ins PHP-Fehlerprotokoll.
 */
function hk_diagnose_output($label, $callback)
{
    ob_start();

    $callback();

    $output = ob_get_clean();

    if ($output !== '') {
        error_log(
            'HK OUTPUT IN ' . $label . ': '
            . strlen($output) . ' Bytes | HEX: '
            . bin2hex($output)
            . ' | TEXT: '
            . var_export($output, true)
        );
    }
}

// Klassen laden
hk_diagnose_output('class-hk-storage.php', function () {
    require_once HK_PLUGIN_DIR . 'includes/class-hk-storage.php';
});

hk_diagnose_output('class-hk-catalog.php', function () {
    require_once HK_PLUGIN_DIR . 'includes/class-hk-catalog.php';
});

hk_diagnose_output('class-hk-woocommerce.php', function () {
    require_once HK_PLUGIN_DIR . 'includes/class-hk-woocommerce.php';
});

hk_diagnose_output('class-hk-settings.php', function () {
    require_once HK_PLUGIN_DIR . 'includes/class-hk-settings.php';
});

hk_diagnose_output('class-hk-frontend.php', function () {
    require_once HK_PLUGIN_DIR . 'includes/class-hk-frontend.php';
});

hk_diagnose_output('class-hk-plugin.php', function () {
    require_once HK_PLUGIN_DIR . 'includes/class-hk-plugin.php';
});

hk_diagnose_output('class-hk-wc-origin.php', function () {
    require_once HK_PLUGIN_DIR . 'includes/woocommerce/class-hk-wc-origin.php';
});

hk_diagnose_output('class-hk-assets.php', function () {
    require_once HK_PLUGIN_DIR . 'includes/class-hk-assets.php';
});

// Aktivierung
register_activation_hook(
    HK_PLUGIN_FILE,
    ['HK_Storage', 'activate']
);

// Klassen initialisieren
hk_diagnose_output('HK_Plugin::instance()', function () {
    HK_Plugin::instance();
});

hk_diagnose_output('HK_WooCommerce::instance()', function () {
    HK_WooCommerce::instance();
});

hk_diagnose_output('HK_WC_Origin::instance()', function () {
    HK_WC_Origin::instance();
});

hk_diagnose_output('HK_Assets::init()', function () {
    HK_Assets::init();
});

hk_diagnose_output('HK_Settings::init()', function () {
    HK_Settings::init();
});

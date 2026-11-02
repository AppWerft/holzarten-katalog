<?php

if (!defined('ABSPATH')) {
    exit;
}

class HK_WC_Origin
{
    private static $instance = null;

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        add_action(
            'woocommerce_product_options_general_product_data',
            [$this, 'render_fields']
        );

        add_action(
            'woocommerce_admin_process_product_object',
            [$this, 'save_fields']
        );
        add_action(
            'admin_enqueue_scripts',
            [$this, 'enqueue_admin_assets']
        );
    }
    /**
 * Leaflet und eigene Skripte im Produkteditor laden.
 */
public function enqueue_admin_assets($hook)
{
    // Nur auf Produkt-Bearbeitungsseiten laden.
    if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
        return;
    }

    $screen = get_current_screen();

    if (!$screen || $screen->post_type !== 'product') {
        return;
    }

    // Leaflet-CSS lokal laden.
    wp_enqueue_style(
        'hk-leaflet',
        HK_PLUGIN_URL . 'assets/vendor/leaflet/leaflet.css',
        [],
        '1.9.4'
    );

    // Leaflet-JavaScript lokal laden.
    wp_enqueue_script(
        'hk-leaflet',
        HK_PLUGIN_URL . 'assets/vendor/leaflet/leaflet.js',
        [],
        '1.9.4',
        true
    );

    // Unsere Kartenbedienung laden.
    wp_enqueue_script(
        'hk-origin-picker',
        HK_PLUGIN_URL . 'assets/js/hk-origin-picker.js',
        ['hk-leaflet'],
        HK_VERSION,
        true
    );
}
    /**
     * Herkunftskoordinaten im Produkteditor anzeigen.
     */
    public function render_fields()
    {
        global $post;

        $product = wc_get_product($post->ID);

        if (!$product) {
            return;
        }

        echo '<div class="options_group">';

        woocommerce_wp_text_input([
            'id'          => '_hk_origin_lat',
            'label'       => 'Breitengrad',
            'type'        => 'number',
            'custom_attributes' => [
                'step' => 'any',
            ],
        ]);

        woocommerce_wp_text_input([
            'id'          => '_hk_origin_lng',
            'label'       => 'Längengrad',
            'type'        => 'number',
            'custom_attributes' => [
                'step' => 'any',
            ],
        ]);

        echo '<button type="button" class="button" id="hk-origin-picker">';
        echo 'Herkunft auf Karte wählen';
        echo '</button>';

        echo '<div id="hk-origin-map" style="display:none;height:400px;margin:10px 12px;"></div>';

        echo '</div>';
    }

    /**
     * Herkunftskoordinaten speichern.
     */
    public function save_fields($product)
    {
        $coordinates = [
            '_hk_origin_lat' => ['min' => -90, 'max' => 90],
            '_hk_origin_lng' => ['min' => -180, 'max' => 180],
        ];

        foreach ($coordinates as $key => $range) {
            if (!isset($_POST[$key])) {
                continue;
            }

            $value = trim(wp_unslash($_POST[$key]));

            if ($value === '' || !is_numeric($value)) {
                $product->delete_meta_data($key);
                continue;
            }

            $value = (float) $value;

            if ($value >= $range['min'] && $value <= $range['max']) {
                $product->update_meta_data($key, $value);
            }
        }
    }
}
    <?php

    if (!defined('ABSPATH')) {
        exit;
    }

    class HOLZKATALOG_WC_Origin
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

            $settings = wp_parse_args(
                get_option('hk_map_settings', []),
                ['priority' => 35]
            );

            add_action(
                'woocommerce_single_product_summary',
                [$this, 'render_map'], // tatsächlichen Methodennamen einsetzen
                $settings['priority']
            );
            /*
            add_action(
                'wp_enqueue_scripts',
                [$this, 'enqueue_scripts']
            );*/
            add_action(
                'woocommerce_admin_process_product_object',
                [$this, 'limit_wood_attribute'],
                20
            );
            add_action(
                'admin_enqueue_scripts',
                [$this, 'enqueue_admin_scripts']
            );
        }

        public function limit_wood_attribute($product)
        {
            $attributes = $product->get_attributes();

            if (!isset($attributes['pa_holzart'])) {
                return;
            }

            $attribute = $attributes['pa_holzart'];

            if (!$attribute->is_taxonomy()) {
                return;
            }

            $options = $attribute->get_options();

            if (count($options) > 1) {
                $attribute->set_options(array_slice($options, 0, 1));
                $attributes['pa_holzart'] = $attribute;

                $product->set_attributes($attributes);
            }
        }


        public static function register_zoomlevel_attribute()
        {
            if (!function_exists('wc_create_attribute')) {
                return;
            }

            if (!wc_attribute_taxonomy_id_by_name('zoomlevel')) {
                $result = wc_create_attribute([
                    'name'         => 'Zoomlevel',
                    'slug'         => 'zoomlevel',
                    'type'         => 'select',
                    'order_by'     => 'menu_order',
                    'has_archives' => false,
                ]);

                if (is_wp_error($result)) {
                    return;
                }

                delete_transient('wc_attribute_taxonomies');
            }

            // WooCommerce muss die Taxonomie zunächst registrieren.
            if (!taxonomy_exists('pa_zoomlevel')) {
                return;
            }

            for ($zoom = 1; $zoom <= 19; $zoom++) {
                if (!term_exists((string) $zoom, 'pa_zoomlevel')) {
                    wp_insert_term((string) $zoom, 'pa_zoomlevel');
                }
            }
        }


        public static function register_zoomlevel_terms()
        {
            if (!taxonomy_exists('pa_zoomlevel')) {
                return;
            }

            for ($zoom = 1; $zoom <= 19; $zoom++) {
                if (!term_exists((string) $zoom, 'pa_zoomlevel')) {
                    wp_insert_term(
                        (string) $zoom,
                        'pa_zoomlevel'
                    );
                }
            }
        }
        /**
         * Karte auf der Produktseite anzeigen.
         */
        public function render_map()
        {
            global $product;

            if (!$product) {
                return;
            }

            $product_id = $product->get_id();

            // Prüfen, ob das Attribut Geoposition zugeordnet ist.
            if (!has_term('', 'pa_geoposition', $product_id)) {
                return;
            }

            // Koordinaten auslesen.
            $lat = get_post_meta($product_id, '_hk_origin_lat', true);
            $lng = get_post_meta($product_id, '_hk_origin_lng', true);

            if ($lat === '' || $lng === '') {
                $product->update_meta_data('_hk_origin_zoom', 6);
                return;
            }
            $zoom_terms = wc_get_product_terms(
                $product_id,
                'pa_zoomlevel',
                ['fields' => 'names']
            );

            $zoom = !empty($zoom_terms)
                ? absint($zoom_terms[0])
                : 6;
            wp_enqueue_script(
                'hk-geopositions-map',
                plugins_url('assets/js/geopositions-map.js', HOLZKATALOG_PLUGIN_FILE),
                ['jquery'],
                '1.0.0',
                true
            );
            $settings = wp_parse_args(
                get_option('hk_map_settings', []),
                [
                    'height' => 200,
                    'width' => 100,
                    'position' => 'center',
                ]
            );

            $margin = match ($settings['position']) {
                'left'  => '0 auto 0 0',
                'right' => '0 0 0 auto',
                default => '0 auto',
            };

            printf(
                '<div data-geo="%s"
                        class="geopositions_map"
                        style="height:%dpx;width:%d%%;margin:%s">
                    </div>',
                esc_attr($lat . ',' . $lng),
                absint($settings['height']),
                absint($settings['width']),
                esc_attr($margin)
            );
        }
        public function enqueue_admin_scripts($hook)
        {
            if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
                return;
            }

            $screen = get_current_screen();

            if (!$screen || $screen->post_type !== 'product') {
                return;
            }

            wp_enqueue_style(
                'hk-admin',
                HOLZKATALOG_PLUGIN_URL . 'assets/css/hk-admin.css',
                [],
                HOLZKATALOG_VERSION
            );

            wp_enqueue_script(
                'hk-admin',
                HOLZKATALOG_PLUGIN_URL . 'assets/js/hk-admin.js',
                ['jquery', 'wc-enhanced-select'],
                HOLZKATALOG_VERSION,
                true
            );
        }


        public function render_geosuche($zoom = 6)
        {
            $settings = wp_parse_args(
                get_option('hk_map_settings', []),
                [
                    'pingcolor'     => '#4285F4',
                    'tile_provider' => 'OpenStreetMap.Mapnik',
                    'height'        => 200,
                    'width'         => '100%',
                    'radius'        => 0,
                    'position'      => 'left'
                ]
            );
            $options = [
                'pingcolor'     => $settings['pingcolor'],
                'tileprovider'  => $settings['tile_provider'],
                'zoom'          => (int) $zoom,
                'height'        => (int) $settings['height'],
                'width'         => $settings['width'],
                'radius'        => (int) $settings['radius'],
                'position'      => $settings['position']
            ];

            return '
        <div id="hk-origin-map-wrapper">
            <div
                id="hk-origin-map"
                data-options="' . esc_attr(wp_json_encode($options)) . '"
            ></div>
        </div>
    ';
        }
        /**
         * Herkunftskoordinaten im Produkteditor anzeigen.
         */
        public function render_fields()
        {
            echo '<div class="options_group">';

            $product_id = get_the_ID();

            $lat  = get_post_meta($product_id, '_hk_origin_lat', true);
            $lng  = get_post_meta($product_id, '_hk_origin_lng', true);
            $zoom = get_post_meta($product_id, '_hk_origin_zoom', true);

            if ($zoom === '') {
                $zoom = 6;
            }

            $zoom = (int) $zoom;

            woocommerce_wp_hidden_input([
                'id'    => '_hk_origin_lat',
                'value' => $lat,
            ]);

            woocommerce_wp_hidden_input([
                'id'    => '_hk_origin_lng',
                'value' => $lng,
            ]);

            woocommerce_wp_hidden_input([
                'id'    => '_hk_origin_zoom',
                'value' => $zoom
            ]);

            echo '</div>';

            echo wp_kses_post($this->render_geosuche($zoom));

            echo '<div style="margin-bottom:20px; display:flex; align-items:center; gap:10px;">';

            echo '<button
                    onclick="window.location.reload()"
                    type="button"
                    class="button"
                    id="hk-settings-cancel">
                    Abbrechen
                </button>';

            submit_button(
                __('Aktualisieren'),
                'primary',
                'save',
                false,
                ['id' => 'hk-save-product-bottom']
            );

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
            // Nonce-Prüfung.
            if (
                !isset($_POST['woocommerce_meta_nonce']) ||
                !check_admin_referer(
                    'woocommerce_save_data',
                    'woocommerce_meta_nonce'
                )
            ) {
                return;
            }



            if (isset($_POST['_hk_origin_zoom'])) {

                $zoom = filter_var(
                    wp_unslash($_POST['_hk_origin_zoom']),
                    FILTER_VALIDATE_INT
                );

                if ($zoom !== false && $zoom >= 0 && $zoom <= 19) {

                    $product->update_meta_data('_hk_origin_zoom', $zoom);
                }
            }

            foreach ($coordinates as $key => $range) {
                if (!isset($_POST[$key])) {
                    continue;
                }

                $value = sanitize_text_field(wp_unslash($_POST[$key]));

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

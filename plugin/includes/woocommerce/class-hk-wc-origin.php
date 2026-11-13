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

            $settings = wp_parse_args(
                get_option('hk_map_settings', []),
                ['priority' => 35]
            );

            add_action(
                'woocommerce_single_product_summary',
                [$this, 'render_map'], // tatsächlichen Methodennamen einsetzen
                $settings['priority']
            );

            add_action(
                'wp_enqueue_scripts',
                [$this, 'enqueue_scripts']
            );
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
                return;
            }
            wp_enqueue_script(
                'hk-geopositions-map',
                plugins_url('assets/js/geopositions-map.js', HK_PLUGIN_FILE),
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
                $settings['height'],
                $settings['width'],
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

            wp_enqueue_script(
                'hk-admin',
                HK_PLUGIN_URL . 'assets/js/hk-admin.js',
                ['jquery', 'wc-enhanced-select'],
                HK_VERSION,
                true
            );
        }
        public function render_geosuche()
        {
            $bar = "";
            // Ortssuche
            $bar .= '<div class="form-field" style="
            display:grid;
           
            grid-template-columns: 16.5% 83.5%;
            align-items:center;
        ">';

            // Label
            $bar .=  '<label for="hk-origin-search-input" style="margin: 10px;">';
            $bar .=  'Ortssuche';
            $bar .=  '</label>';

            // Container für Eingabefeld und Trefferliste
            $bar .=  '<div style="position:relative;width:50%;">';

            // Input
            $bar .=  '<input type="search"
            id="hk-origin-search-input"
            placeholder="Ort oder Adresse eingeben …"
            style="
                width:100%;
                background-image:url(\'' . plugins_url('assets/images/lupe.svg', HK_PLUGIN_FILE) . '\');
                background-repeat:no-repeat;
                background-position:right 12px center;
                background-size:20px 20px;
                box-sizing:border-box;
            ">';

            // Trefferliste
            $bar .=  '<div id="hk-origin-search-results" style="
            top:100%;
            left:0;
            right:0;
            z-index:100000;
            color:black;
            background:#ccc;
            border:1px solid #eee;
            border-top:none;
            max-height:320px;
            overflow-y:auto;
            box-shadow:0 4px 8px rgba(0,0,0,0.15);"
            data-icon-url="' . esc_url(plugins_url('assets/images/geoposition.webp', HK_PLUGIN_FILE)) . '"
            
            ></div>';

            $bar .=  '</div>';
            $bar .=  '</div>';

            // Button
            $bar .=  '<p class="form-field">';
            $bar .=  '<label>&nbsp;</label>';
            $bar .=  '<button style="display:none;height:0"
            type="button"
            class="button"
            id="hk-origin-picker">';
            $bar .=  'Herkunft auf Karte wählen';
            $bar .=  '</button>';
            $bar .=  '</p>';
            $bar .=  '<div id="hk-origin-map" style="display:none;height:400px;margin:10px 12px;"></div>';
            return $bar;
        }
        /**
         * Herkunftskoordinaten im Produkteditor anzeigen.
         */
        public function render_fields()
        {
            error_log('HK_WC_Origin::render_fields aufgerufen');
            error_log(
                'HK_ORIGIN: ' . wp_debug_backtrace_summary()
            );
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
            echo "</div>";
            echo $this->render_geosuche();
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

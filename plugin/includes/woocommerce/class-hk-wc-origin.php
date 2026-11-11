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
        }


        public function render_geosuche()
        {
            $bar = "";
            // Ortssuche
            $bar .= '<div class="form-field" style="
            display:grid;
            grid-template-columns: 16.5% 90%;
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
